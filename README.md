# required+ WP Top Content
**Contributors**: [hubeRsen](https://github.com/hubeRsen), [neverything](https://github.com/neverything)  
**Tags:** analytics, content  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin to sync google anaytics data (pageviews & visits) with posts and pages.

# Installation
After you've installed and activated the plugin please go to the plugin settings screen and fill in all needed fields. You'll find informations on how to get the Google API options for each setting.

# This plugin offers
Besides the synchronisation of Google Analytics data you'll get a widget/shortcode/function to display/list the top contents of your blog.

## Widget
There is a widget you can drag 'n drop into your defined widget areas. You can select of which post type your top content should be listed of.

## Shortcode
The shortcode is named **rplus-topcontent** and can get the following parameters:

- *Int* **count**: The count of posts/pages to display
- *String* **posttypes**: Comma separated list of post types to include in the top contents list
- *String* **template**: The override template name to use for the output of each top content element. By default *rplus-wp-top-content.php* will be used.

### Example Usage
    [rplus-topcontent count="5" posttypes="post,page,customtype"]


## Template function
The template function is named **rplus_wp_top_content** and will receive 3 optional parameters

- *Array* **$post_types**: Array of post_types the top contents should be of
- *Int*   **$count**: The limit of top contents to display
- *String* **$template**: The template that should be used to display a top content item
 

## Customisation
### Filters

- **rplus_wp_top_content_default_args** - change query args for top content list
- **rplus_wp_top_content_default_classes** - change default css class names of top content list entries
- **rplus_wp_top_content_classes** - change css class names of top content list entries
- **rplus_wp_top_content_widget_list_start** - change the list start element (by default the widget outputs a ul)
- **rplus_wp_top_content_widget_list_end** - change the list end element (by default the widget outputs a /ul)

### Templates
By default there are 2 different Templates. One for the items of the top content widget, and one for the shortcode / template function.  
You can override this templates in your own theme, by simply placing the files in your theme root directory. The plugin will then load your template, when it exists.

- **public/templates/rplus-wp-top-content.php**: Used to output a top content item for the shortcode or template function
- **public/templates/rplus-wp-top-content-widget.php**: Used to output a top content item for the widget. By default, this should be a list element. The widget will surround the elements with a ul & /ul. To change that, see the different **filters**

# required+
[required+](http://required.ch) is a network of experienced web professionals from Switzerland and Germany. We focus on Interaction Design, Mobile Web, WordPress and some other things.