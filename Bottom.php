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
		<a href="#body" class="a11y-hidden BottomButton">
			<?php echo _( 'Skip to main content' ); // Accessibility link to skip menus. ?>
		</a>
		<a id="BottomButtonMenu" href="#!" title="<?php echo AttrEscape( _( 'Menu' ) ); ?>" class="BottomButton">
			<svg viewBox="0 0 24 24">
				<line x1="3" y1="12" x2="21" y2="12"></line>
				<line x1="3" y1="6" x2="21" y2="6"></line>
				<line x1="3" y1="18" x2="21" y2="18"></line>
			</svg>
			<span><?php echo _( 'Menu' ); ?></span>
		</a>

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
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
				<span><?php echo $back_text; ?></span>
			</a>

		<?php endif;

		/**
		 * Do bottom_buttons action hook
		 *
		 * @see also 'ProgramFunctions/Bottom.fnc.php|bottom_buttons' action hook
		 */
		do_action( 'Bottom.php|bottom_buttons' ); ?>

		<a id="BottomButtonPrint" href="" target="_blank" title="<?php echo AttrEscape( _( 'Print' ) ); ?>" class="BottomButton">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V3h12v6"/><rect x="4" y="9" width="16" height="8" rx="2"/><path d="M8 17h8v4H8z"/></svg>
			<span><?php echo _( 'Print' ); ?></span>
		</a>
		<a id="BottomButtonHelp" href="#!" title="<?php echo AttrEscape( _( 'Help' ) ); ?>" class="BottomButton">
			<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.6 9a2.5 2.5 0 1 1 3.4 2.3c-.8.4-1 .9-1 1.7"/><path d="M12 17h.01"/></svg>
			<span><?php echo _( 'Help' ); ?></span>
		</a>
		<a href="<?php echo URLEscape( 'index.php?modfunc=logout&token=' . $_SESSION['token'] ); ?>" target="_top" title="<?php echo AttrEscape( _( 'Logout' ) ); ?>" class="BottomButton">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
			<span><?php echo _( 'Logout' ); ?></span>
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
