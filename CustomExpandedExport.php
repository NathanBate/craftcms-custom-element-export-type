<?php

namespace modules;

Use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\exporters\Expanded;
use craft\errors\InvalidFieldException;
use craft\helpers\Db;
use craft\elements\Entry;

/**
 * Check out Craft's page on how to create your own custom exporter:
 * https://craftcms.com/docs/3.x/extend/element-exporter-types.html#creating-custom-exporter-types
 */
class CustomExpandedExport extends Expanded
{

    public static function displayName(): string
    {
        return 'Expanded with Asset URLs';
    }

    /**
     * This export method was adapted from craft\elements\exporters\expanded to give more information
     * on related assets so that FeedMe would work properly. The first half of this code is exactly
     * the same as the expanded class referenced above.
     *
     * @param ElementQueryInterface $query
     * @return array
     * @throws InvalidFieldException
     */
    public function export(ElementQueryInterface $query): array
    {
        // Eager-load as much as we can
        $eagerLoadableFields = [];
        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            if ($field instanceof EagerLoadingFieldInterface) {
                $eagerLoadableFields[] = $field->handle;
            }
        }

        $data = [];

        /** @var ElementQuery $query */
        $query->with($eagerLoadableFields);

        foreach (Db::each($query) as $element) {

            /** @var ElementInterface $element */
            // Get the basic array representation excluding custom fields
            $attributes = array_flip($element->attributes());
            if (($fieldLayout = $element->getFieldLayout()) !== null) {
                foreach ($fieldLayout->getFields() as $field) {
                    unset($attributes[$field->handle]);
                }
            }
            $elementArr = $element->toArray(array_keys($attributes));
            if ($fieldLayout !== null) {
                foreach ($fieldLayout->getFields() as $field) {


                    /**
                     * The following lines of code diverge from the Default Craft Code so
                     * that we can accomplish our objective.
                     */
                    $value = $element->getFieldValue($field->handle);

                    /**
                     * Some debug logging
                     */
                    //Craft::info("field exported");
                    //Craft::info($field);

                    /**
                     * Check to see if this is an asset relation field
                     * https://craftcms.stackexchange.com/questions/23920/how-to-check-an-elements-elementtype
                     */
                    if (is_a($field, 'craft\fields\Assets')) {

                        $fieldHandle = $field->handle;

                        /**
                         * Get this entry so that we can get the related asset information that we want.
                         */
                        $entry = Entry::find()
                            ->id([$element->id])
                            ->with([$fieldHandle])
                            ->one();


                        /**
                         * Place to store all the prepped asset information for this asset relation field.
                         */
                        $preppedAssets = [];

                        /**
                         * Log some debug info
                         */
                        //Craft::info("$fieldHandle on entry");
                        //Craft::info($entry[$fieldHandle]);


                        /**
                         * Loop through the related assets in this asset field and build the data
                         */
                        if (isset($entry[$fieldHandle])) {
                            foreach ($entry[$fieldHandle] as $asset) {

                                /**
                                 * Make sure the file exists before adding it to the export. On some
                                 * sites there can be some asset corruption that causes related assets
                                 * to actually be missing on the disk. If this is the case, then we
                                 * don't want to include them in the export because it will cause
                                 * Feed-Me to choke.
                                 */
                                if ($asset->volume->fileExists($asset->path)) {
                                    $preppedAsset = [];


                                    /**
                                     * Let's loop through the custom fields and look for an altText
                                     * custom field. If it exists, then save that altText value
                                     */
                                    $assetFields = $asset->getFieldLayout()->getFields();
                                    foreach($assetFields as $assetField) {
                                        if ($assetField->handle == "altText") {
                                            $preppedAsset['altText'] = $asset->getFieldValue($assetField->handle);
                                        }
                                    }


                                    /**
                                     * Check out the following CraftCMS Class References
                                     *
                                     * https://docs.craftcms.com/api/v3/craft-elements-asset.html
                                     * https://docs.craftcms.com/api/v3/craft-base-volumeinterface.html
                                     * https://docs.craftcms.com/api/v3/craft-volumes-local.html
                                     */
                                    $preppedAsset['id'] = $asset->id;
                                    $preppedAsset['title'] = $asset->title;
                                    $preppedAsset['url'] = $asset->url;
                                    //$preppedAsset['path'] = $asset->path;
                                    //$preppedAsset['folderPath'] = $asset->folderPath;
                                    //$preppedAsset['filename'] = $asset->filename;
                                    //$preppedAsset['tempFilePath'] = $asset->tempFilePath;
                                    //$preppedAsset['volume'] = $asset->volume;
                                    $preppedAsset['fileExists'] = $asset->volume->fileExists($asset->path);
                                    //$preppedAsset['asset'] = $asset;


                                    /**
                                     * Check to see if this is a local volume versus an object storage volume
                                     * because a non-local volume will likely not have a path property.
                                     */
                                    if (is_a($asset->volume, 'craft\volumes\Local')) {
                                        //$preppedAsset['volumePath'] = $asset->volume->path;
                                    }

                                    /**
                                     * Now lets throw another asset into the array
                                     */
                                    $preppedAssets[] = $preppedAsset;

                                }

                            } // foreach

                        } // if isset


                        /**
                         * Build the export for this asset relation field
                         */
                        $elementArr[$field->handle] =  $preppedAssets;

                    } else {
                        $elementArr[$field->handle] = $field->serializeValue($value, $element);
                    }

                }
            }
            $data[] = $elementArr;
        }

        return $data;
    }


}