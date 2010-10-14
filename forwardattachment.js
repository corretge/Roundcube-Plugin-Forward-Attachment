/**
 * ForwardAttachment plugin script
 */

if (window.rcmail) {
	rcmail.addEventListener('init', function(evt) {
		// register command (directly enable in message view mode)
		rcmail.register_command('plugin.forwardatt', rcmail_forwardatt, rcmail.env.uid);

		// add event-listener to message list
		if (rcmail.message_list) {
			rcmail.message_list.addEventListener('select', function(list) {
				rcmail.enable_command('plugin.forwardatt', list.get_selection().length > 0);
			});
		}
	})
}

function rcmail_forwardatt(prop) {
	if (!rcmail.env.uid && (!rcmail.message_list || !rcmail.message_list.get_selection().length))
		return;

	var prev_sel = null;

	// also select childs of (collapsed) threads
	if (rcmail.message_list) {
		if (rcmail.env.uid) {
			if (rcmail.message_list.rows[rcmail.env.uid].has_children && !rcmail.message_list.rows[rcmail.env.uid].expanded) {
				if (!rcmail.message_list.in_selection(rcmail.env.uid)) {
					prev_sel = rcmail.message_list.get_selection();
					rcmail.message_list.select_row(rcmail.env.uid);
				}

				rcmail.message_list.select_childs(rcmail.env.uid);
				rcmail.env.uid = null;
			}
			else if (rcmail.message_list.get_single_selection() == rcmail.env.uid) {
				rcmail.env.uid = null;
			}
		}
		else {
			selection = rcmail.message_list.get_selection();
			for (var i in selection) {
				if (rcmail.message_list.rows[selection[i]].has_children && !rcmail.message_list.rows[selection[i]].expanded)
					rcmail.message_list.select_childs(selection[i]);
			}
		}
	}

	var uids = rcmail.env.uid ? rcmail.env.uid : rcmail.message_list.get_selection().join(',');

	var lock = rcmail.set_busy(true, 'loading');
	rcmail.http_post('plugin.forwardatt', '_uid='+uids+'&_mbox='+urlencode(rcmail.env.mailbox), lock);

	if (prev_sel) {
		rcmail.message_list.clear_selection();

		for (var i in prev_sel)
			rcmail.message_list.select_row(prev_sel[i], CONTROL_KEY);
	}
}

function rcmail_forwardatt_status(command) {
	switch (command) {
		case 'beforedelete':
			if (!rcmail.env.flag_for_deletion && rcmail.env.trash_mailbox &&
				rcmail.env.mailbox != rcmail.env.trash_mailbox &&
				(rcmail.message_list && !rcmail.message_list.shiftkey))
				rcmail.enable_command('plugin.forwardatt', false);

			break;
		case 'beforemove':
		case 'beforemoveto':
			rcmail.enable_command('plugin.forwardatt', false);
			break;
		case 'aftermove':
		case 'aftermoveto':
			if (rcmail.env.action == 'show')
				rcmail.enable_command('plugin.forwardatt', true);

			break;
		case 'afterpurge':
		case 'afterexpunge':
			if (!rcmail.env.messagecount && rcmail.task == 'mail')
				rcmail.enable_command('plugin.forwardatt', false);

			break;
	}
}

function rcmail_forwardatt_init() {
	if (window.rcm_contextmenu_register_command) {
		rcm_contextmenu_register_command('forwardatt', 'rcmail_forwardatt', rcmail.gettext('forwardattachment.buttontitle'), 'delete', null, true);
		$('#rcmContextMenu li.forwardatt').addClass('forward');
	}
}

rcmail.add_onload('rcmail_forwardatt_init()');

// update button activation after external events
rcmail.addEventListener('beforedelete', function(props) { rcmail_forwardatt_status('beforedelete'); } );
rcmail.addEventListener('beforemove', function(props) { rcmail_forwardatt_status('beforemove'); } );
rcmail.addEventListener('beforemoveto', function(props) { rcmail_forwardatt_status('beforemoveto'); } );
rcmail.addEventListener('aftermove', function(props) { rcmail_forwardatt_status('aftermove'); } );
rcmail.addEventListener('aftermoveto', function(props) { rcmail_forwardatt_status('aftermoveto'); } );
rcmail.addEventListener('afterpurge', function(props) { rcmail_forwardatt_status('afterpurge'); } );
rcmail.addEventListener('afterexpunge', function(props) { rcmail_forwardatt_status('afterexpunge'); } );