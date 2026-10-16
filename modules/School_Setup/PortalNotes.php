<?php
require_once 'ProgramFunctions/PortalPollsNotes.fnc.php';
require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

DrawHeader( ProgramTitle() );

// Add eventual Dates to $_REQUEST['values'].
AddRequestedDates( 'values', 'post' );

if ( $_REQUEST['modfunc'] === 'update'
	&& AllowEdit() )
{
	$_REQUEST['values'] = issetVal( $_REQUEST['values'], [] );

	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		if ( isset( $columns['PUBLISHED_PROFILES'] ) )
		{
			$published_profiles = '';

			foreach ( (array) $columns['PUBLISHED_PROFILES'] as $profile )
			{
				if ( $profile != '' )
				{
					$published_profiles .= ',' . $profile;
				}
			}

			$columns['PUBLISHED_PROFILES'] = '';

			if ( $published_profiles )
			{
				$columns['PUBLISHED_PROFILES'] = $published_profiles . ',';
			}
		}

		// FJ fix SQL bug invalid sort order.

		if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
		{
			// FJ textarea fields MarkDown sanitize.

			if ( isset( $columns['CONTENT'] ) )
			{
				$columns['CONTENT'] = DBEscapeString( SanitizeMarkDown( $_POST['values'][$id]['CONTENT'] ) );
			}

			if ( $id !== 'new' )
			{
				DBUpdate(
					'portal_notes',
					$columns,
					[ 'ID' => (int) $id ]
				);

				//hook
				do_action( 'School_Setup/PortalNotes.php|update_portal_note' );
			}

			// New: check for Title.
			elseif ( $columns['TITLE'] )
			{
				// @since 10.9 Fix security issue, unset any FILE_ATTACHED column first.
				$columns['FILE_ATTACHED'] = '';

				if ( ! empty( $_FILES['FILE_ATTACHED_FILE']['name'] ) )
				{
					// @since 12.0 Use $FileUploadsPath . 'PortalNotes/' instead of $PortalNotesFilesPath
					$portal_notes_files_path = ! empty( $PortalNotesFilesPath ) ?
						$PortalNotesFilesPath :
						$FileUploadsPath . 'PortalNotes/';

					// File attached to portal notes
					$columns['FILE_ATTACHED'] = FileUpload(
						'FILE_ATTACHED_FILE',
						$portal_notes_files_path,
						FileExtensionWhiteList(),
						0,
						$error,
						'',
						FileNameTimestamp( $_FILES['FILE_ATTACHED_FILE']['name'] )
					);
				}
				elseif ( filter_var( $columns['FILE_ATTACHED_EMBED'], FILTER_VALIDATE_URL ) !== false )
				{
					$columns['FILE_ATTACHED'] = $columns['FILE_ATTACHED_EMBED'];
				}

				unset( $columns['FILE_ATTACHED_EMBED'] );

				$insert_columns = [
					'SCHOOL_ID' => UserSchool(),
					'SYEAR' => UserSyear(),
					'PUBLISHED_USER' => User( 'STAFF_ID' ),
				];

				if ( empty( $error ) )
				{
					// Global var used by Moodle plugin.
					$portal_note_id = DBInsert(
						'portal_notes',
						$insert_columns + $columns,
						'id'
					);

					//hook
					do_action( 'School_Setup/PortalNotes.php|create_portal_note' );
				}
			}
		}
		else
		{
			$error[] = _( 'Please enter a valid Sort Order.' );
		}
	}

	// Unset modfunc & values & profiles & redirect URL.
	RedirectURL( [ 'modfunc', 'values', 'profiles' ] );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Note' ) ) )
	{
		// FJ file attached to portal notes.
		$file_to_remove = DBGetOne( "SELECT FILE_ATTACHED
			FROM portal_notes
			WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		// Security: use FileDelete() instead of unlink()
		FileDelete( $file_to_remove );

		DBQuery( "DELETE FROM portal_notes WHERE ID='" . (int) $_REQUEST['id'] . "'" );

		//hook
		do_action( 'School_Setup/PortalNotes.php|delete_portal_note' );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	// File attached to portal notes.
	$notes_RET = DBGet( "SELECT ID,SORT_ORDER,TITLE,CONTENT,START_DATE,END_DATE,PUBLISHED_PROFILES,FILE_ATTACHED,
		CASE WHEN END_DATE IS NOT NULL AND END_DATE<CURRENT_DATE THEN 'Y' ELSE NULL END AS EXPIRED
	FROM portal_notes
	WHERE SCHOOL_ID='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'
	ORDER BY EXPIRED DESC,SORT_ORDER IS NULL,SORT_ORDER,CREATED_AT DESC", [
		'TITLE' => '_makeTextInput',
		'CONTENT' => '_makeContentInput',
		'SORT_ORDER' => '_makeTextInput',
		'FILE_ATTACHED' => 'makeFileAttached',
		'START_DATE' => 'makePublishing'
	] );

	$columns = [
		'TITLE' => _( 'Title' ),
		'CONTENT' => _( 'Note' ),
		'SORT_ORDER' => _( 'Sort Order' ),
		'FILE_ATTACHED' => _( 'File Attached' ),
		'START_DATE' => _( 'Publishing Options' ),
	];

	$notes_are_empty = empty( $notes_RET );

	echo '<section class="ethio-onboarding" aria-labelledby="portal-notes-intro-title">' .
		'<div class="ethio-onboarding-icon" aria-hidden="true">' .
		'<img src="assets/themes/' . AttrEscape( Preferences( 'THEME' ) ) . '/modules/School_Setup.svg" alt="">' .
		'</div><div class="ethio-onboarding-copy">' .
		'<span class="ethio-onboarding-kicker">' . ( $notes_are_empty ? _( 'A simple way to share important updates' ) : _( 'Keep your school community informed' ) ) . '</span>' .
		'<h3 id="portal-notes-intro-title">' . ( $notes_are_empty ? _( 'There are no notes for this school year yet.' ) : _( 'Share a clear update with families and staff.' ) ) . '</h3>' .
		'<p>' . ( $notes_are_empty ? _( 'Start with a title and message below. You can add a document and choose who can see your note and when it appears.' ) : _( 'Write a note, add a document if needed, then choose who can see it and when it should appear.' ) ) . '</p>' .
		'<a class="ethio-onboarding-link" href="#portal-note-editor">' . ( $notes_are_empty ? _( 'Start your first note' ) : _( 'Create a new note' ) ) . ' <span aria-hidden="true">→</span></a>' .
		'</div></section>';

	//,'START_TIME' => 'Start Time','END_TIME' => 'End Time'
	$link['add']['html'] = [
		'TITLE' => _makeTextInput( '', 'TITLE' ),
		'CONTENT' => _makeContentInput( '', 'CONTENT' ),
		'SHORT_NAME' => _makeTextInput( '', 'SHORT_NAME' ),
		'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		'FILE_ATTACHED' => makeFileAttached( '', 'FILE_ATTACHED' ),
		'START_DATE' => makePublishing( '', 'START_DATE' ),
	];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
	$link['remove']['variables'] = [ 'id' => 'ID' ];

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) . '" method="POST" enctype="multipart/form-data">';
	echo '<div class="portal-list-toolbar"><div><h3>' . _( 'Your notes' ) . '</h3>' .
		'<p>' . _( 'Draft and manage the messages shown in the school portal.' ) . '</p></div>' .
		'<div class="portal-list-actions">' . SubmitButton() . '</div></div>';

	echo '<div id="portal-note-editor"' . ( $notes_are_empty ? ' class="portal-empty-list"' : '' ) . '>';
	ListOutput( $notes_RET, $columns, 'Note', 'Notes', $link );
	echo '</div>';
	echo '</form>';
}

/**
 * @param $value
 * @param $name
 */
function _makeTextInput( $value, $name )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	$extra = '';

	if ( $name === 'SORT_ORDER' )
	{
		$extra = ' type="number" min="-9999" max="9999"';
	}
	elseif ( $name !== 'TITLE' )
	{
		$extra = 'size=5 maxlength=10';
	}
	elseif ( $id !== 'new' )
	{
		$extra = 'required';
	}

	return TextInput(
		( $name == 'TITLE' && ! empty( $THIS_RET['EXPIRED'] ) ?
			[ $value, '<span style="color:red">' . $value . '</span>' ] :
			$value ),
		'values[' . $id . '][' . $name . ']',
		'',
		$extra
	);
}

/**
 * @param $value
 * @param $name
 * @return mixed
 */
function _makeContentInput( $value, $name )
{
	global $THIS_RET;

	$id = ! empty( $THIS_RET['ID'] ) ? $THIS_RET['ID'] : 'new';

	$return = '<div id="divNoteContent' . $id . '" class="rt2colorBox">' .
		TextAreaInput( $value, "values[" . $id . "][" . $name . "]", '', 'rows=5' ) .
		'</div>';

	return $return;
}
