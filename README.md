# CraftCMS Custom Element Export Type - Expanded with Asset URLs

Putting this here for reference and sharing with others just in case. There may
be a better way of doing this, but I couldn't find one after searching the internet.

When you use CraftCMS's element exporter, any related assets to an entry 
are just exported as an asset ID. If you need to migrate these assets to
another website, their asset IDs will surely be different. 

After searching online, the only way I found to do this so that it works 
with [CraftCMS's Feed-Me](https://github.com/craftcms/feed-me) is to write an a 
[Custom Element Export Type](https://craftcms.com/docs/3.x/extend/element-exporter-types.html).
However, I wanted to have all the features of the [Expanded Exporter Type](https://docs.craftcms.com/api/v3/craft-elements-exporters-expanded.html)
but with Asset URLs (for Feed-Me) instead of the Asset IDs.

This module code extends the functionality of the default Expanded Element Export
Type already in Craft and adds asset URLs as well as an asset custom field with the
handle `altText`. 