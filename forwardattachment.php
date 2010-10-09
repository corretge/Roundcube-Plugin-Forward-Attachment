<?php

/**
 * ForwardAttachment
 *
 * Plugin to allow users to forward a message as an attachment
 *
 * @version @package_version@
 * @author Roland Liebl
 * @modified by Philip Weir
 */
class forwardattachment extends rcube_plugin
{
	public $task = 'mail';

	function init()
	{
		$this->register_action('plugin.forwardatt', array($this, 'attach_message'));

		$rcmail = rcmail::get_instance();
		if ($rcmail->action == '' || $rcmail->action == 'show') {
			$this->add_hook('render_mailboxlist', array($this, 'add_menu'));
			$this->add_hook('render_page', array($this, 'init_menu'));
		}
	}

	function add_menu($args)
	{
		$this->add_texts('localization', true);
		$this->include_script('forwardattachment.js');
		$li = '';

		$forward = $this->api->output->button(array('command' => 'forward', 'label' => 'forwardmessage', 'class' => 'forwardlink', 'classact' => 'forwardlink active'));
		$forwardattachment = $this->api->output->button(array('command' => 'plugin.forwardatt', 'label' => 'forwardattachment.buttontitle', 'class' => 'forwardattlink', 'classact' => 'forwardattlink active'));

		$li .= html::tag('li', null, $forward);
		$li .= html::tag('li', null, $forwardattachment);
		$out .= html::tag('ul', null, $li);

		$this->api->output->add_footer(html::div(array('id' => 'forwardmenu', 'class' => 'popupmenu'), $out));
	}

	function init_menu($args)
	{
		$args['content'] = preg_replace("/(rcube_init_mail_ui\(\))/", "rcube_init_mail_ui(); rcmail_ui.popups.forwardmenu = {id: 'forwardmenu', obj:$('#forwardmenu')};", $args['content']);
		$args['content'] = preg_replace("/(<a[^>]*onclick=\"return rcmail.command\('forward','',this\)\"[^>]*>\s*<\/a>)/", "<span class=\"dropbutton\">$1<span id=\"forwardmenulink\" onclick=\"rcmail_ui.show_popup('forwardmenu');return false\"></span></span>", $args['content']);

		return $args;
	}

	function attach_message()
	{
		$rcmail = rcmail::get_instance();
		$imap = $rcmail->imap;
		$uids = get_input_value('_uid', RCUBE_INPUT_POST);
		$temp_dir = $rcmail->config->get('temp_dir');

		if (isset($_POST['_uid'])) {
			rcmail_compose_cleanup();
			$_SESSION['compose'] = array(
				'id' => uniqid(rand()),
				'mailbox' => $imap->get_mailbox_name(),
			);

			$uids = explode(",", $_POST['_uid']);
			foreach ($uids as $key => $uid) {
				$file = tempnam($temp_dir, 'emlattach');
				$message = $imap->get_raw_body($uid);
				$headers = $imap->get_headers($uid);

				foreach($headers as $key => $val){
					if ($key == 'subject') {
						$subject = (string)substr($imap->decode_header($val, TRUE), 0, 16);
						break;
					}
				}

				if (isset($subject) && $subject != "") {
					$disp_name = preg_replace("/[^0-9A-Za-z_]/", '_', $subject) . ".eml";

					if(preg_match('/^_*\.eml$/', $disp_name))
						$disp_name = "message_rfc822.eml";
				}
				else {
					$disp_name = "message_rfc822.eml";
				}

				if (file_put_contents($file, $message)) {
					$attachment = array(
						'path' => $file,
						'size' => filesize($file),
						'name' => $disp_name,
						'mimetype' => "message/rfc822"
						);

					// save attachment if valid
					if (($attachment['data'] && $attachment['name']) || ($attachment['path'] && file_exists($attachment['path'])))
						$attachment = $rcmail->plugins->exec_hook('attachment_save', $attachment);

					if ($attachment['status'] && !$attachment['abort']) {
						unset($attachment['data'], $attachment['status'], $attachment['abort']);
						$_SESSION['compose']['attachments'][] = $attachment;
					}
				}
			}
		}

		$_SESSION['compose']['param']['sent_mbox'] = $rcmail->config->get('sent_mbox');
		$rcmail->output->redirect(array('_action' => 'compose', '_id' => $_SESSION['compose']['id']));
		exit;
	}
}

?>