<?php
namespace TYPO3\CMS\Reports\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Ingo Renner <ingo@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A task that should be run regularly to determine the system's status.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage reports
 */
class SystemStatusUpdateTask extends \TYPO3\CMS\Scheduler\Task {

	/**
	 * Email address to send email notification to in case we find problems with
	 * the system.
	 *
	 * @var string
	 */
	protected $notificationEmail = NULL;

	/**
	 * Executes the System Status Update task, determing the highest severity of
	 * status reports and saving that to the registry to be displayed at login
	 * if necessary.
	 *
	 * @see typo3/sysext/scheduler/tx_scheduler_Task::execute()
	 */
	public function execute() {
		$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$statusReport = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status');
		$systemStatus = $statusReport->getSystemStatus();
		$highestSeverity = $statusReport->getHighestSeverity($systemStatus);
		$registry->set('tx_reports', 'status.highestSeverity', $highestSeverity);
		if ($highestSeverity > \TYPO3\CMS\Reports\Status::OK) {
			$this->sendNotificationEmail($systemStatus);
		}
		return TRUE;
	}

	/**
	 * Gets the notification email address.
	 *
	 * @return string Notification email address.
	 */
	public function getNotificationEmail() {
		return $this->notificationEmail;
	}

	/**
	 * Sets the notification email address.
	 *
	 * @param string $notificationEmail Notification email address.
	 */
	public function setNotificationEmail($notificationEmail) {
		$this->notificationEmail = $notificationEmail;
	}

	/**
	 * Sends a notification email, reporting system issues.
	 *
	 * @param array $systemStatus Array of statuses
	 */
	protected function sendNotificationEmail(array $systemStatus) {
		$systemIssues = array();
		foreach ($systemStatus as $statusProvider) {
			foreach ($statusProvider as $status) {
				if ($status->getSeverity() > \TYPO3\CMS\Reports\Report\Status\Status::OK) {
					$systemIssues[] = (string) $status;
				}
			}
		}
		$subject = sprintf($GLOBALS['LANG']->getLL('status_updateTask_email_subject'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
		$message = sprintf($GLOBALS['LANG']->getLL('status_problemNotification'), '', '');
		$message .= CRLF . CRLF;
		$message .= ($GLOBALS['LANG']->getLL('status_updateTask_email_site') . ': ') . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		$message .= CRLF . CRLF;
		$message .= ($GLOBALS['LANG']->getLL('status_updateTask_email_issues') . ': ') . CRLF;
		$message .= implode(CRLF, $systemIssues);
		$message .= CRLF . CRLF;
		$from = \TYPO3\CMS\Core\Utility\MailUtility::getSystemFrom();
		$mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
		$mail->setFrom($from);
		$mail->setTo($this->notificationEmail);
		$mail->setSubject($subject);
		$mail->setBody($message);
		$mail->send();
	}

}


?>