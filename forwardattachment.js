/* Forwardasattachment plugin script */

if (window.rcmail) {
	rcmail.addEventListener('init', function(evt) {
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

	var uids = rcmail.env.uid ? rcmail.env.uid : rcmail.message_list.get_selection().join(',');

	rcmail.set_busy(true, 'loading');
	rcmail.http_post('plugin.forwardatt', '_uid='+uids+'&_mbox='+urlencode(rcmail.env.mailbox), true);
}

function rcmail_forwardatt_init()
{
	if (window.rcm_contextmenu_register_command)
		rcm_contextmenu_register_command('forwardatt', 'rcmail_forwardatt', rcmail.gettext('forwardattachment.buttontitle'), 'delete', null, true);

}

rcmail.add_onload('rcmail_forwardatt_init()');