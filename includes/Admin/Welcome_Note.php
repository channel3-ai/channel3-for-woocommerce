<?php
/**
 * Welcome Note for Admin Inbox
 *
 * @package Channel3
 */

namespace Channel3\Admin;

defined( 'ABSPATH' ) || exit;

// Check for Admin Note support.
if ( ! class_exists( 'Automattic\WooCommerce\Admin\Notes\Note' ) ||
	! trait_exists( 'Automattic\WooCommerce\Admin\Notes\NoteTraits' ) ) {
	return;
}

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

/**
 * Channel3 Welcome Note Class
 *
 * Adds a welcome note to the merchant inbox on plugin activation.
 */
class Welcome_Note {

	use NoteTraits;

	/**
	 * Note name/identifier.
	 */
	const NOTE_NAME = 'channel3-welcome-note';

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		$note = new Note();

		$note->set_title( __( 'Connect your store to Channel3', 'channel3-for-woocommerce' ) );

		$note->set_content(
			__( 'Sync your WooCommerce product catalog to Channel3 and reach more customers. Connect your store to get started.', 'channel3-for-woocommerce' )
		);

		$note->set_content_data( (object) array(
			'plugin_activated' => true,
			'activated_time'   => time(),
		) );

		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_layout( 'plain' );
		$note->set_image( '' );

		// Set note name and source for namespacing.
		$note->set_source( 'channel3-for-woocommerce' );
		$note->set_name( self::NOTE_NAME );

		// Add action button to go to settings.
		$note->add_action(
			'channel3-connect',
			__( 'Get Started', 'channel3-for-woocommerce' ),
			admin_url( 'admin.php?page=wc-settings&tab=integration&section=channel3' ),
			Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);

		return $note;
	}

	/**
	 * Possibly add the note.
	 *
	 * Checks if the note should be added and adds it if appropriate.
	 */
	public static function possibly_add_note() {
		// Don't add if already connected.
		if ( function_exists( 'channel3_is_connected' ) && channel3_is_connected() ) {
			return;
		}

		// Check if note already exists using data store.
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );
		if ( ! empty( $note_ids ) ) {
			return;
		}

		$note = self::get_note();
		$note->save();
	}

	/**
	 * Possibly delete the note.
	 *
	 * Deletes the welcome note if it exists.
	 */
	public static function possibly_delete_note() {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );

		foreach ( $note_ids as $note_id ) {
			$note = new Note( $note_id );
			$note->delete();
		}
	}
}
