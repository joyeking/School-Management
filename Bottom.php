<?php
/**
 * Bottom
 *
 * Displays bottom menu
 * Handles Print & Inline Help functionalities
 *
 * @package RosarioSIS
 */

require_once 'Warehouse.php';

if ( isAJAX() )
{
	ETagCache( 'start' );
}

// Output Bottom menu.
if ( empty( $_REQUEST['bottomfunc'] ) ) : ?>

	<div id="footerwrap">
		<?php // EthioSIS: logo first, top-left, before the Menu button. ?>
		<a href="<?php echo URLEscape( 'Modules.php?modname=misc/Portal.php' ); ?>" class="topbar-logo" title="<?php echo AttrEscape( _( 'Home' ) ); ?>">
			<img src="assets/themes/<?php echo Preferences( 'THEME' ); ?>/logo.png" class="logo" alt="<?php echo AttrEscape( Config( 'NAME' ) ); ?>">
		</a>
		<a href="#body" class="a11y-hidden BottomButton">
			<?php echo _( 'Skip to main content' ); // Accessibility link to skip menus. ?>
		</a>
		<a id="BottomButtonMenu" href="#!" title="<?php echo AttrEscape( _( 'Menu' ) ); ?>" class="BottomButton">
			<svg viewBox="0 0 256 256" aria-hidden="true"><path d="M228,128a12,12,0,0,1-12,12H40a12,12,0,0,1,0-24H216A12,12,0,0,1,228,128ZM40,76H216a12,12,0,0,0,0-24H40a12,12,0,0,0,0,24ZM216,180H40a12,12,0,0,0,0,24H216a12,12,0,0,0,0-24Z"/></svg>
			<span><?php echo _( 'Menu' ); ?></span>
		</a>

		<?php // EthioSIS: school / year / quarter selects live here (filled in by the theme's JS). ?>
		<div id="topbar-context" aria-label="<?php echo AttrEscape( _( 'Context' ) ); ?>"></div>

		<?php // FJ icons.

		$btn_path = 'assets/themes/' . Preferences( 'THEME' ) . '/btn/';

		if ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' ) :

			$back_url = issetVal( $_SESSION['List_PHP_SELF'], '' );

			switch ( issetVal( $_SESSION['Back_PHP_SELF'], '' ) )
			{
				case 'student':

					$back_text = _( 'Student List' );
				break;

				case 'staff':

					$back_text = _( 'User List' );
				break;

				case 'course':

					$back_text = _( 'Course List' );
				break;

				default:

					$back_text = sprintf( _( '%s List' ), $_SESSION['Back_PHP_SELF'] );
			}

			/**
			 * Remove need to make an AJAX call to Bottom.php
			 * Which represented up to 10% of total AJAX requests
			 *
			 * @since 12.0 JS Show BottomButtonBack & update its URL & text
			 * @see BottomButtonBackUpdate() function
			 */
			?>

			<a href="<?php echo URLEscape( $back_url ); ?>" title="<?php echo AttrEscape( $back_text ); ?>"
				id="BottomButtonBack" class="BottomButton<?php echo $back_url ? '' : ' hide'; ?>">
				<svg viewBox="0 0 256 256" aria-hidden="true"><path d="M228,128a12,12,0,0,1-12,12H69l51.52,51.51a12,12,0,0,1-17,17l-72-72a12,12,0,0,1,0-17l72-72a12,12,0,0,1,17,17L69,116H216A12,12,0,0,1,228,128Z"/></svg>
				<span><?php echo $back_text; ?></span>
			</a>

		<?php endif;

		/**
		 * Do bottom_buttons action hook
		 *
		 * @see also 'ProgramFunctions/Bottom.fnc.php|bottom_buttons' action hook
		 */
		do_action( 'Bottom.php|bottom_buttons' ); ?>

		<?php
		/**
		 * EthioSIS: light / dark scheme toggle sits where Logout used to be.
		 *
		 * The chosen scheme lives in the ethio_scheme cookie, which
		 * Warehouse.php stamps onto <html data-theme>, so this renders the
		 * correct icon and label on the very first paint (no JS flash).
		 * Both glyphs are inline SVG; CSS shows the one for the other
		 * scheme so the swap costs no request.
		 *
		 * @see Warehouse.php  $ethio_scheme
		 * @see Side.php       Logout, moved to the bottom of the sidebar.
		 */
		$ethio_is_dark = isset( $_COOKIE['ethio_scheme'] )
			&& $_COOKIE['ethio_scheme'] === 'dark';

		$ethio_scheme_label = $ethio_is_dark ? _( 'Light mode' ) : _( 'Dark mode' ); ?>

		<button type="button" id="BottomButtonScheme" class="BottomButton"
			title="<?php echo AttrEscape( $ethio_scheme_label ); ?>"
			aria-label="<?php echo AttrEscape( $ethio_scheme_label ); ?>"
			aria-pressed="<?php echo $ethio_is_dark ? 'true' : 'false'; ?>"
			data-scheme="<?php echo $ethio_is_dark ? 'dark' : 'light'; ?>">
			<svg viewBox="0 0 256 256" aria-hidden="true" class="scheme-icon scheme-icon-dark"><path d="M116,36V20a12,12,0,0,1,24,0V36a12,12,0,0,1-24,0Zm80,92a68,68,0,1,1-68-68A68.07,68.07,0,0,1,196,128Zm-24,0a44,44,0,1,0-44,44A44.05,44.05,0,0,0,172,128ZM51.51,68.49a12,12,0,1,0,17-17l-12-12a12,12,0,0,0-17,17Zm0,119-12,12a12,12,0,0,0,17,17l12-12a12,12,0,1,0-17-17ZM196,72a12,12,0,0,0,8.49-3.51l12-12a12,12,0,0,0-17-17l-12,12A12,12,0,0,0,196,72Zm8.49,115.51a12,12,0,0,0-17,17l12,12a12,12,0,0,0,17-17ZM48,128a12,12,0,0,0-12-12H20a12,12,0,0,0,0,24H36A12,12,0,0,0,48,128Zm80,80a12,12,0,0,0-12,12v16a12,12,0,0,0,24,0V220A12,12,0,0,0,128,208Zm108-92H220a12,12,0,0,0,0,24h16a12,12,0,0,0,0-24Z"/></svg>
			<svg viewBox="0 0 256 256" aria-hidden="true" class="scheme-icon scheme-icon-light"><path d="M236.37,139.4a12,12,0,0,0-12-3A84.07,84.07,0,0,1,119.6,31.59a12,12,0,0,0-15-15A108.86,108.86,0,0,0,49.69,55.07,108,108,0,0,0,136,228a107.09,107.09,0,0,0,64.93-21.69,108.86,108.86,0,0,0,38.44-54.94A12,12,0,0,0,236.37,139.4Zm-49.88,47.74A84,84,0,0,1,68.86,69.51,84.93,84.93,0,0,1,92.27,48.29Q92,52.13,92,56A108.12,108.12,0,0,0,200,164q3.87,0,7.71-.27A84.79,84.79,0,0,1,186.49,187.14Z"/></svg>
			<span><?php echo $ethio_scheme_label; ?></span>
		</button>
		<?php // EthioSIS: Print sits at the far right of the top bar. ?>
		<a id="BottomButtonPrint" href="" target="_blank" title="<?php echo AttrEscape( _( 'Print' ) ); ?>" class="BottomButton">
			<svg viewBox="0 0 256 256" aria-hidden="true"><path d="M214.67,68H204V40a12,12,0,0,0-12-12H64A12,12,0,0,0,52,40V68H41.33C25.16,68,12,80.56,12,96v80a12,12,0,0,0,12,12H52v28a12,12,0,0,0,12,12H192a12,12,0,0,0,12-12V188h28a12,12,0,0,0,12-12V96C244,80.56,230.84,68,214.67,68ZM76,52H180V68H76ZM180,204H76V172H180Zm40-40H204v-4a12,12,0,0,0-12-12H64a12,12,0,0,0-12,12v4H36V96c0-2.17,2.44-4,5.33-4H214.67c2.89,0,5.33,1.83,5.33,4Zm-16-44a16,16,0,1,1-16-16A16,16,0,0,1,204,120Z"/></svg>
			<span><?php echo _( 'Print' ); ?></span>
		</a>
		<span class="loading BottomButton"></span>
	</div>

	<div id="footerhelp"><div class="footerhelp-content"></div></div>
<?php
// Print PDF.
elseif ( $_REQUEST['bottomfunc'] === 'print' ) :

	if ( ! isset( $_REQUEST['modfunc'] ) )
	{
		$_REQUEST['modfunc'] = false;
	}

	// Force search_modfunc to list.
	if ( Preferences( 'SEARCH' ) !== 'Y' )
	{
		$_REQUEST['search_modfunc'] = 'list';
	}
	elseif ( ! isset( $_REQUEST['search_modfunc'] ) )
	{
		$_REQUEST['search_modfunc'] = '';
	}

	if ( ! empty( $_REQUEST['expanded_view'] ) )
	{
		$_SESSION['orientation'] = 'landscape';
	}

	// FJ call PDFStart to generate Print PDF.
	$print_data = PDFStart();

	$modname = $_REQUEST['modname'];

	if ( ! $wkhtmltopdfPath )
	{
		$_ROSARIO['allow_edit'] = false;
	}

	if ( AllowUse() )
	{
		if ( mb_substr( $modname, -4, 4 ) !== '.php'
			|| mb_strpos( $modname, '..' ) !== false
			/*|| ! is_file( 'modules/' . $modname )*/ )
		{
			require_once 'ProgramFunctions/HackingLog.fnc.php';
			HackingLog();
		}
		else
			require_once 'modules/' . $modname;

	}
	// Not allowed, hacking attempt?
	elseif ( User( 'USERNAME' ) )
	{
		require_once 'ProgramFunctions/HackingLog.fnc.php';
		HackingLog();
	}

	// FJ call PDFStop to generate Print PDF.
	PDFStop( $print_data );


// Inline Help.
elseif ( $_REQUEST['bottomfunc'] === 'help' ) :

	require_once 'ProgramFunctions/Help.fnc.php';

	$help_text = GetHelpText( $_REQUEST['modname'] );

	echo $help_text;

endif;

if ( isAJAX() )
{
	ETagCache( 'stop' );
}
