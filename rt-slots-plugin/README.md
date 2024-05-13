# Raketech Slots Plugin
The Raketech Slots Plugin serves as an integration tool for consuming slots from SlotsLibrary within a WordPress website. It facilitates the retrieval of all available slots and providers for seamless integration.

# Requirements
- ACF Pro plugin installed
- Slot Library URL - input the URL on the plugin Slots Settings

# Features
Within the plugin dashboard, you'll find multiple tabs:

- Slots: Displays all items under your designated postType.
- Add new Slot: Allows for the addition of new slots without the need for a slots manager.
- Game Types: Custom Taxonomy for categorizing games.
- Casino Software: Custom Taxonomy for Providers. Providers can be imported from the Slots Manager and are automatically added when a new slot is added from the Slots Manager.
- Slots Manager: There are two tabs on Slots Manager
    - Providers: Enables the addition or updating of Providers on the website.
    - Slots:  Facilitates the addition, publication, or updating of slots on the website. Additionally, an "Update Lists" button clears request cache.
- Slots Settings: Settings page for the plugin.

# Changelog:
- 1.0.0 - Initial version
- 1.0.1 - Add composer
- 1.0.2 - Add offline slots
- 1.0.3 - Add Slotjava custom options
- 1.0.4 - Bug fixes, upadate taxonomy and default post type
- 1.0.5 - Change default CPT to "slots" and add likes and plays counts to slots data
- 1.0.6 - Update return format for image field
- 1.0.7 - Fix slot provider taxonomy and image upload issues
- 1.0.8 - Performance improvements and Update return format for image field
- 1.0.9 - Fix cache clearing and update offline status logic in slots API and AJAX
- 1.1.0 - Add token and credentials to access SL
- 1.1.1 - Improvements on load priorities and ACF json
- 1.1.2 - Update iframe URLs
- 1.1.3 - Update slot provider taxonomy and fix content being deleted issue