<?php
namespace GDO\Lounge;

use GDO\Core\Debug;
use GDO\Core\GDO_Module;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_Int;
use GDO\Core\GDT_String;
use GDO\Crypto\GDT_MD5;
use GDO\Crypto\GDT_PasswordHash;
use GDO\Date\GDT_Duration;
use GDO\Login\GDO_LoginAttempt;
use GDO\Login\GDO_LoginHistory;
use GDO\Net\GDT_Hostname;
use GDO\Net\GDT_Port;
use GDO\Net\GDT_Url;
use GDO\Register\GDO_UserActivation;
use GDO\UI\GDT_Link;
use GDO\UI\GDT_Page;
use GDO\User\GDO_User;
use GDO\User\GDT_ACLRelation;

/**
 * Login module for GDOv7.
 *
 * - Login History
 * - Bruteforce Protection
 * - Optional Captcha
 * - LoginAs any user (staff)
 * - Warnings on failed logins (optionally show attacker IP to affected user)
 *
 * @version 7.0.2
 * @since 3.0.0
 * @author gizmore@wechall.net
 */
final class Module_Lounge extends GDO_Module
{

	public int $priority = 90;

	##############
	### Module ###
	##############
	public function getDependencies(): array
    {
        return ['Cronjob'];
    }

    public function onLoadLanguage(): void { $this->loadLanguage('lang/lounge'); }


	##############
	### Config ###
	##############
	public function getConfig(): array
	{
		return [
			GDT_Hostname::make('lounge_server')->initial('irc.wechall.net'),
            GDT_Port::make('lounge_port')->initial('6667'),
			GDT_Checkbox::make('lounge_tls')->initial('0'),
			GDT_String::make('lounge_channel')->initial('#wechall'),
            GDT_Url::make('lounge_url')->allowExternal()->reachable()->initial('//'.GDO_DOMAIN.':9000'),
            GDT_MD5::make('lounge_config_hash'),
		];
	}

    public function cfgServer(): string { return $this->getConfigVar('lounge_server'); }
    public function cfgPort(): string { return $this->getConfigVar('lounge_port'); }
    public function cfgTLS(): bool { return $this->getConfigVar('lounge_tls'); }
    public function cfgChannel(): string { return $this->getConfigVar('lounge_channel'); }
    public function cfgURL(): string { return $this->getConfigVar('lounge_url'); }
    public function cfgConfigHash(): string { return $this->getConfigVar('lounge_config_hash'); }

    public function onIncludeScripts(): void
    {
    }

    public function onInitSidebar(): void
	{
        GDT_Page::instance()->leftBar()->addField(GDT_Link::make()->text('module_lounge')->href($this->cfgURL()));
	}

}
