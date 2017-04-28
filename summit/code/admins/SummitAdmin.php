<?php

class SummitAdmin extends ModelAdmin implements PermissionProvider
{
    private static $url_segment = 'summits';

    public $showImportForm = false;

    private static $managed_models = array
    (
        'Summit',
        'SummitType',
    );

    public function init()
    {
        parent::init();
        Requirements::javascript('summit/javascript/GridFieldApprovePushNotificationAction.js');
        $res = Permission::check("ADMIN") || Permission::check("ADMIN_SUMMIT_APP") || Permission::check("ADMIN_SUMMIT_APP_SCHEDULE");
        if (!$res) {
            Security::permissionFailure();
        }
    }

    private static $menu_title = 'Summits';

    public function providePermissions() {
        return array(
            'ADMIN_SUMMIT_APP' => array(
                'name'     => 'Full Access to Summit CMS Admin',
                'category' => 'Summit Application',
                'help'     => '',
                'sort'     => 0
            ),
            'ADMIN_SUMMIT_APP_SCHEDULE' => array(
                'name'     => 'Full Access to Summit CMS Schedule Admin',
                'category' => 'Summit Application',
                'help'     => '',
                'sort'     => 1
            ),
        );
    }
}