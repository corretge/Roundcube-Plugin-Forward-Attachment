/**
 * ForwardAttachment plugin script
 */

function rcmail_forwardatt(prop) {
	if (rcmail.message_list && rcmail.message_list.get_selection().length > 1) {
		// also select childs of (collapsed) threads
		if (rcmail.env.uid) {
			if (rcmail.message_list.rows[rcmail.env.uid].has_children && !rcmail.message_list.rows[rcmail.env.uid].expanded) {
				if (!rcmail.message_list.in_selection(rcmail.env.uid))
					rcmail.message_list.select_row(rcmail.env.uid);

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

		var uids = rcmail.env.uid ? rcmail.env.uid : rcmail.message_list.get_selection().join(',');

		var lock = rcmail.set_busy(true, 'loading');
		rcmail.http_post('plugin.forwardatt', '_uid='+uids+'&_mbox='+urlencode(rcmail.env.mailbox), lock);

		return false;
	}
}

function rcmail_forwardatt_init() {
	var idx = -1;

	// check for browser support if Array.indexOf
	if (Array.prototype.indexOf) {
		idx = rcmail.contextmenu_disable_multi.indexOf('#forward-attachment');
	}
	else {
		for (i = 0; i < rcmail.contextmenu_disable_multi.length; i++) {
			if (rcmail.contextmenu_disable_multi[i] == '#forward-attachment')
				idx = i;
		}
	}

	if (window.rcm_contextmenu_register_command && idx != -1)
		delete rcmail.contextmenu_disable_multi[idx];
}

$(document).ready(function() {
	if (window.rcmail) {
		rcmail.addEventListener('init', function(evt) {
			// add event-listener to message list
			if (rcmail.message_list) {
				rcmail.message_list.addEventListener('select', function(list) {
					rcmail.enable_command('forward-attachment', list.get_selection().length > 0);
				});
			}
		});

		rcmail.add_onload('rcmail_forwardatt_init()');

		// override default forward attachment function
		rcmail.addEventListener('beforeforward-attachment', function(props) { return rcmail_forwardatt(props); } );
	}
});