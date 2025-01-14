# Post Type Migrator for WordPress

A powerful WordPress plugin that allows you to easily migrate posts between different post types while maintaining all metadata, taxonomies, and automatically creating 301 redirects.

## Features

- Migrate posts between any public post types
- Filter posts by categories before migration
- Batch processing for handling large numbers of posts
- Preserve all post metadata and taxonomies
- Optional deletion of original posts
- Automatic creation of 301 redirects (requires Redirection plugin)
- Progress tracking with detailed success/failure reporting
- Pagination support for large post lists
- Support for all post statuses (publish, draft, private, pending)

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- [Redirection plugin](https://wordpress.org/plugins/redirection/) (optional, for 301 redirects)

## Installation

1. Download the plugin zip file
2. Go to WordPress admin panel → Plugins → Add New
3. Click "Upload Plugin" and select the downloaded zip file
4. Click "Install Now" and then "Activate"

## Usage

1. Navigate to Tools → Post Type Migrator in your WordPress admin panel

2. Select your source:
   - Choose between Post Type or Taxonomy
   - For Post Types: Select the source post type
   - For Posts: Optionally filter by categories

3. Select your destination:
   - Choose the destination post type

4. Select posts to migrate:
   - Use individual checkboxes or "Select All"
   - Navigate through pages if you have many posts

5. Configure migration options:
   - Choose whether to delete original posts

6. Click "Migrate Selected Posts" to begin the migration

## Features in Detail

### Post Selection
- Filter posts by categories
- Paginated post list for better performance
- Bulk selection options
- Display of post status, date, and categories

### Migration Process
- Batch processing to handle large migrations
- Progress tracking with real-time updates
- Detailed success/failure reporting
- Transaction-safe database operations

### Data Preservation
- Maintains all post metadata
- Preserves taxonomies and categories
- Keeps original post dates and author information
- Maintains post status (publish, draft, private, pending)

### SEO Considerations
- Automatic 301 redirects creation (with Redirection plugin)
- Preserves post slugs when possible
- Maintains post hierarchy

## Technical Details

### Supported Post Properties
- Title
- Content
- Excerpt
- Status
- Author
- Date (published and modified)
- Comment status
- Ping status
- Parent post
- Menu order
- Post name (slug)

### Supported Metadata
- All custom fields
- Featured images
- Template settings
- Custom post type specific metadata

## FAQ

**Q: Will the plugin create redirects automatically?**  
A: Yes, if you have the Redirection plugin installed and activated, it will automatically create 301 redirects from the old URLs to the new ones.

**Q: Can I migrate posts in bulk?**  
A: Yes, you can select multiple posts and migrate them in one operation. The plugin processes them in batches for optimal performance.

**Q: What happens to the original posts?**  
A: You can choose to either keep or delete the original posts during migration. This option is available before starting the migration.

**Q: Are taxonomies and metadata preserved?**  
A: Yes, all taxonomies, categories, tags, and custom fields are preserved during migration.

## Support

For support, please create an issue in the plugin's repository or contact the plugin author at forbee.dev@gmail.com.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Basic migration functionality
- Support for all public post types
- Category filtering
- Automatic 301 redirects
- Batch processing
- Progress tracking