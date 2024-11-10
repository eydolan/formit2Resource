<?php

$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');

$defaultParentId=290;
$defaultPublished=0;
$defaultTemplate=2;
$defaultUser=732;

// Load the Tagger service
/*$tagger = $modx->getService('tagger'); // Simplified loading
if (!$tagger) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Could not load Tagger service.');
    return false; // This may also be unnecessary if the service is guaranteed to be available
}*/

// Create new resource
$doc = $modx->newObject('modResource');
$userId = $modx->user->get('id') ? $modx->user->get('id') : $defaultUser;
$doc->set('createdby', $userId);

$allFormFields = $hook->getValues();

// Set core fields
foreach ($allFormFields as $field => $value) {
    if (in_array($field, ['pagetitle', 'content'])) {
        $doc->set($field, $value);
    }
}

// Set other resource properties
$doc->set('parent', $defaultParentId); // Using integer value, which is suitable for IDs
$doc->set('alias', time());
$doc->set('template', $defaultTemplate); 
$doc->set('published', $defaultPublished);

if (!$doc->save()) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Failed to save resource');
    return false;
}

$resourceId = $doc->get('id');
$modx->log(modX::LOG_LEVEL_ERROR, "Resource saved with ID: $resourceId");

// Loop to save template variables (TVs)
foreach ($allFormFields as $field => $value) {
    if ($tv = $modx->getObject('modTemplateVar', ['name' => $field])) {
        if (is_array($value)) {
            $value = implode('||', $value);
        }
        $tv->setValue($resourceId, $value);
        if (!$tv->save()) {
            $modx->log(modX::LOG_LEVEL_ERROR, "Failed to save TV: $field for Resource ID: $resourceId");
        }
    }
}

// Add tags using Tagger for each tag type
$tagFields = [
    'subject_tags' => 'subject_tagger_group_id',
    'topic_tags' => 'topic_tagger_group_id',
    'grade_tags' => 'grade_tagger_group_id',
    'material_type_tags' => 'material_type_tagger_group_id',
    'editor_tags' => 'editor_tagger_group_id',
    'language_tags' => 'language_tagger_group_id',
];

   foreach ($tagFields as $tagField => $groupField) {
       
       if (!empty($allFormFields[$tagField]) && !empty($allFormFields[$groupField])) {
           
           $tags = explode(',', $allFormFields[$tagField]);
           $taggerGroupId = (int) $allFormFields[$groupField];

           $modx->log(modX::LOG_LEVEL_ERROR, "Processing tags for $tagField in group $taggerGroupId");

           foreach ($tags as $tag) {
               
               $tag = trim($tag);
               
               if (!empty($tag)) {
                   // Check if the tag already exists
                   $tagObject = $modx->getObject('Tagger\Model\TaggerTag', [
                       'tag' => $tag,
                       'group' => $taggerGroupId,
                   ]);

                   // If the tag does not exist, create it
                   if (!$tagObject) {
                       $tagObject = $modx->newObject('Tagger\Model\TaggerTag');
                       $tagObject->set('tag', $tag);
                       $tagObject->set('group', $taggerGroupId);
                       if (!$tagObject->save()) {
                           $modx->log(modX::LOG_LEVEL_ERROR, "Failed to create Tag: $tag in Group: $taggerGroupId");
                           continue; 
                       }
                   }

                   // Set the resource ID on the tag object
                   $tagObject->set('resource', $resourceId);

                   // Link the tag to the resource
                   if (!$tagObject->addMany($doc)) {
                       $modx->log(modX::LOG_LEVEL_ERROR, "Failed to link Tag: $tag to Resource ID: $resourceId");
                   } else {
                       $modx->log(modX::LOG_LEVEL_ERROR, "Linked Tag: $tag to Resource ID: $resourceId");
                   }
                   
               }
               
           }
           
       }
       
   }
   
return true;
