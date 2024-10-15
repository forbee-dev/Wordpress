# Rt SportsIndex Plugin
The Rt SportsIndex Plugin serves as an integration tool for consuming Sports Stats from SportsIndex within a WordPress website. It facilitates the retrieval of all available Matches and Tournaments for seamless integration.

# Requirements
- ACF Pro plugin installed
- Sports Index URL - input the URL on the plugin Sports Options page
- API Key - input the API key on the plugin Sports Options page

# Features
Within the plugin dashboard, you'll find multiple tabs:

- Tournaments: Displays all items under your designated postType.
- Matches: Displays all items under your designated postType.
- Add new Match/Tournament: Allows for the addition of new Matches/Tournaments without connection with SportsIndex
- Sports Manager: There are two tabs on Sports Manager
    - Tournaments: Enables the addition or updating of Tournaments on the website.
    - Matches: Facilitates the addition, publication, or updating of Matches on the website. Additionally, an "Update Lists" button clears request cache.
- Sports Options: Settings page for the plugin.
- Tournament-Matches: Display the corresponding Matches for a specific Tournament.

# Changelog:
- 1.0.0 - Initial version
- 1.0.1 - Add suport to Authors on CPTs
- 1.0.2 - Add ACF field Match Time
- 1.0.3 - Add OCB specific settings
- 1.0.4 - Plugin refactor to handle new requirements - TODO: Remove old Matches logic
- 1.0.5 - Add with_front false to CPTs
- 1.0.6 - Add search by Match or Tournament shortname
- 1.0.7 - Fields are now set as required for validation
- 1.0.8 - Update all matches data (Cron ready for future implementeation but commented out)
        - Add Format to matches call
        - Fix match time