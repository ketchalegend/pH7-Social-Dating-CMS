<?php
/**
 * @author         Pierre-Henry Soria <hello@ph7cms.com>
 * @copyright      (c) 2012-2018, Pierre-Henry Soria. All Rights Reserved.
 * @license        GNU General Public License; See PH7.LICENSE.txt and PH7.COPYRIGHT.txt in the root directory.
 * @package        PH7 / App / System / Core / Form / Processing
 */

namespace PH7;

defined('PH7') or exit('Restricted access');

use PH7\Framework\Mvc\Request\Http;

/** For "user", "affiliate" and "admin" modules **/
class ChangePasswordCoreFormProcess extends Form
{
    /**
     * @internal Need to use Http::NO_CLEAN arg in Http::post() since password might contains special character like "<" and will otherwise be converted to HTML entities
     */
    public function __construct()
    {
        parent::__construct();

        // PH7\UserCoreModel::login() method of the UserCoreModel Class works only for "user" and "affiliate" module.
        $sClassName = ($this->registry->module === PH7_ADMIN_MOD) ? AdminModel::class : UserCoreModel::class;
        $oPasswordModel = new $sClassName;

        $sEmail = $this->getUserEmail();
        $sTable = $this->getTableName();

        // Login
        if ($this->registry->module === PH7_ADMIN_MOD) {
            $mLogin = $oPasswordModel->adminLogin(
                $sEmail,
                $this->session->get('admin_username'),
                $this->httpRequest->post('old_password', Http::NO_CLEAN)
            );
        } else {
            $mLogin = $oPasswordModel->login(
                $sEmail,
                $this->httpRequest->post('old_password', Http::NO_CLEAN),
                $sTable
            );
        }

        // Check
        if ($this->httpRequest->post('new_password', Http::NO_CLEAN) !== $this->httpRequest->post('new_password2', Http::NO_CLEAN)) {
            \PFBC\Form::setError('form_change_password', t("The passwords don't match."));
        } elseif ($this->httpRequest->post('old_password', Http::NO_CLEAN) === $this->httpRequest->post('new_password', Http::NO_CLEAN)) {
            \PFBC\Form::setError('form_change_password', t('Your current and new passwords are identical. So why do you want to change it?'));
        } elseif ($mLogin !== true) {
            \PFBC\Form::setError('form_change_password', t("Your current password isn't correct."));
        } else {
            // Regenerate the session ID to prevent session fixation attack
            $this->session->regenerateId();

            // Update the password
            $oPasswordModel->changePassword($sEmail, $this->httpRequest->post('new_password', Http::NO_CLEAN), $sTable);
            \PFBC\Form::setSuccess('form_change_password', t('Your password has been successfully changed.'));
        }
    }

    /**
     * @return string
     */
    private function getUserEmail()
    {
        if ($this->registry->module === 'user') {
            return $this->session->get('member_email');
        }

        if ($this->registry->module === PH7_ADMIN_MOD) {
            return $this->session->get('admin_email');
        }

        return $this->session->get('affiliate_email');
    }

    /**
     * @return string
     */
    private function getTableName()
    {
        if ($this->registry->module === 'user') {
            return 'Members';
        }

        if ($this->registry->module === PH7_ADMIN_MOD) {
            return 'Admins';
        }

        return 'Affiliates';
    }
}
