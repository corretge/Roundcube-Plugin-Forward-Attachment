/* Forwardasattachment plugin script */

if (window.rcmail) {
	rcmail.addEventListener('init', function(evt) {
		// move button to right place on toolbar and show
		$('#' + rcmail.buttons['plugin.forwardatt'][0].id).insertAfter('#' + rcmail.buttons['forward'][0].id);
		$('#' + rcmail.buttons['plugin.forwardatt'][0].id).show();

		// register command (directly enable in message view mode)
		rcmail.register_command('plugin.forwardatt', rcmail_forwardatt, rcmail.env.uid);

		// add event-listener to message list
		if (rcmail.message_list)
			rcmail.message_list.addEventListener('select', function(list){
				rcmail.enable_command('plugin.forwardatt', list.get_selection().length > 0);
			});
	})
}

function rcmail_forwardatt(prop)
{
	if (!rcmail.env.uid && (!rcmail.message_list || !rcmail.message_list.get_selection().length))
		return;

	var prev_sel = null;

	// also select childs of (collapsed) threads
	if (rcmail.env.uid) {
		if (rcmail.message_list.rows[rcmail.env.uid].has_children && !rcmail.message_list.rows[rcmail.env.uid].expanded) {
			if (!rcmail.message_list.in_selection(rcmail.env.uid)) {
				prev_sel = rcmail.message_list.get_selection();
				rcmail.message_list.select_row(rcmail.env.uid);
			}

			rcmail.message_list.select_childs(rcmail.env.uid);
			rcmail.env.uid = null;
		}
		else if (!rcmail.message_list.in_selection(rcmail.env.uid)) {
			prev_sel = rcmail.message_list.get_single_selection();
			rcmail.message_list.remove_row(rcmail.env.uid, false);
		}
		else if (rcmail.message_list.get_single_selection() == rcmail.env.uid) {
			rcmail.env.uid = null;
		}
	}

	var uids = rcmail.env.uid ? rcmail.env.uid : rcmail.message_list.get_selection().join(',');

	rcmail.set_busy(true, 'loading');
	rcmail.http_post('plugin.forwardatt', '_uid='+uids+'&_mbox='+urlencode(rcmail.env.mailbox), true);

	if (prev_sel) {
		rcmail.message_list.clear_selection();

		for (var i in prev_sel)
			rcmail.message_list.select_row(prev_sel[i], CONTROL_KEY);
	}
}

function rcmail_forwardatt_init()
{
	if (window.rcm_contextmenu_register_command)
		rcm_contextmenu_register_command('forwardatt', 'rcmail_forwardatt', rcmail.gettext('forwardattachment.buttontitle'), 'delete', null, true);

}

rcmail.add_onload('rcmail_forwardatt_init()');