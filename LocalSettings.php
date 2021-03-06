<?php
# This file was automatically generated by the MediaWiki 1.31.1
# installer. If you make manual changes, please keep track in case you
# need to recreate them later.
#
# See includes/DefaultSettings.php for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.
#
# Further documentation for configuration settings may be found at:
# https://www.mediawiki.org/wiki/Manual:Configuration_settings

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}


## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

$wgSitename = "InfyWiki";
$wgMetaNamespace = "Infosys";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs
## (like /w/index.php/Page_title to /wiki/Page_title) please see:
## https://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath = "";
#$wgLogo = $wgScriptPath . '/bitnami/mediawiki/file.png';

## The URL path to static resources (images, scripts, etc.)
$wgResourceBasePath = $wgScriptPath;

## The URL path to the logo.  Make sure you change this from the default,
## or else you'll overwrite your logo when you upgrade!
$wgLogo = "$wgResourceBasePath/images/conff/wiki.png";
$wgFavicon = "$wgResourceBasePath/images/conff/favicon.ico";
## UPO means: this is also a user preference option

$wgEnableEmail = true;
$wgEnableUserEmail = true; # UPO

$wgEmergencyContact = "apache@a052759b49bd";
$wgPasswordSender = "apache@a052759b49bd";

$wgEnotifUserTalk = false; # UPO
$wgEnotifWatchlist = false; # UPO
$wgEmailAuthentication = true;

## Database settings
$wgDBtype = "mysql";
$wgDBserver = "mariadb";
$wgDBname = "bitnami_mediawiki";
$wgDBuser = "bn_mediawiki";
$wgDBpassword = "";

# MySQL specific settings
$wgDBprefix = "";

# MySQL table options to use during installation or update
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

## Shared memory settings
$wgMainCacheType = CACHE_NONE;
$wgMemCachedServers = [];

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads = true;
#$wgUseImageMagick = true;
#$wgImageMagickConvertCommand = "/usr/bin/convert";

# InstantCommons allows wiki to use images from https://commons.wikimedia.org
$wgUseInstantCommons = false;

# Periodically send a pingback to https://www.mediawiki.org/ with basic data
# about this MediaWiki instance. The Wikimedia Foundation shares this data
# with MediaWiki developers to help guide future development efforts.
$wgPingback = false;

## If you use ImageMagick (or any other shell command) on a
## Linux server, this will need to be set to the name of an
## available UTF-8 locale
$wgShellLocale = "C.UTF-8";

## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publically accessible from the web.
#$wgCacheDirectory = "$IP/cache";

#Uncomment below lines to start Ldap
require_once("$IP/extensions/LdapAuthentication/LdapAuthentication.php");
$wgAuth = new LdapAuthenticationPlugin();
$wgLDAPDomainNames = array('AHM',);
$wgLDAPServerNames = array('AHM' => 'azawvifpwindc02.ahm.corp',);
$wgLDAPSearchStrings = array('AHM' => 'AHM\\USER-NAME',);
$wgLDAPEncryptionType = array('AHM' => 'clear',);
$wgLDAPBaseDNs = array('AHM' => 'ou=Users,ou=Unix,dc=AHM,dc=corp');
$wgLDAPSearchAttributes = array('AHM' => 'sAMAccountName');
$wgLDAPProxyAgent = array("AHM"=>"*****");
$wgLDAPProxyAgentPassword = array("AHM"=>"*****");
$wgLDAPUpdateLDAP = array("AHM"=>false);
$wgLDAPAddLDAPUsers = array("AHM"=>false);
$wgLDAPPreferences = array( 'AHM' => true );
$wgLDAPDebug = 99; //for debugging LDAP
$wgShowExceptionDetails = true; //for debugging
$wgDebugLogGroups["ldap"] = "/var/www/mediawiki/debug.log";
$wgLDAPUseLocal = true;
# Site language code, should be one of the list in ./languages/Names.php
#$wgLanguageCode = "en";

# Site language code, should be one of the list in ./languages/data/Names.php
$wgLanguageCode = "en";

$wgSecretKey = "0412e3a3ebbb51e13b0efad14d4ed809ecd8ec4bfa88626ac5da982c9a648625";

# Changing this will log out all existing sessions.
$wgAuthenticationTokenVersion = "1";

# Site upgrade key. Must be set to a string (default provided) to turn on the
# web installer while LocalSettings.php is in place
$wgUpgradeKey = "eb77991ad761eef4";

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = "";
$wgRightsText = "";
$wgRightsIcon = "";

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";

## Default skin: you can change the default skin. Use the internal symbolic
## names, ie 'vector', 'monobook':
$wgDefaultSkin = "erudite";
#require_once "$IP/skins/erudite/erudite.php";
# Enabled skins.
# The following skins were automatically enabled:
wfLoadSkin( 'MonoBook' );
wfLoadSkin( 'Timeless' );
wfLoadSkin( 'Vector' );
wfLoadSkin( 'Gamepress' );
wfLoadSkin( 'Material' );
#wfLoadSkin( 'monaco-port' );
wfLoadSkin( 'erudite' );
# End of automatically generated settings.
# Add more configuration options below.
#require_once "$IP/extensions/TextScroller/TextScroller.php";
require_once("$IP/extensions/videoflash.php");
require_once "$IP/extensions/Widgets/Widgets.php";
wfLoadExtension( 'TextScroller' );
wfLoadExtension( 'InputBox' );
wfLoadExtension( 'YouTube' );
wfLoadSkin( 'pivot' );
$wgPivotFeatures = array(
	'showActionsForAnon' => true,
	'fixedNavBar' => false,
	'usePivotTabs' => false,
	'showHelpUnderTools' => true,
	'showRecentChangesUnderTools' => true,
	'wikiName' => &$GLOBALS['wgSitename'],
	'wikiNameDesktop' => &$GLOBALS['wgSitename'],
	'navbarIcon' => false,
	'preloadFontAwesome' => false,
	'showFooterIcons' => true,
	'addThisPUBID' => '',
	'useAddThisShare' => '',
	'useAddThisFollow' => ''
);
wfLoadExtension( 'Renameuser' );
