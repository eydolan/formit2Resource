<?php

/*
html example:

[[!FormIt?
&hooks=`resource2formitSubmit`
&submitvar=`submittheexampleform`
]]
<form action="[[~[[*id]]]]" method="post">
    <input type="text" name="pagetitle" placeholder="ADD MORE INPUTS THAT YOU NEED BELOW" value="[[!+fi.pagetitle]]">
    <textarea name="introtext" id="introtext">[[!+fi.introtext]]</textarea>
    <textarea name="content">[[!+fi.content]]</textarea>
    <input id="input-tag2" type="text" value="[[TaggerGetTags? &groups=`1` &rowTpl=`tag_tpl`]]" name="subject_tags">
    <input type="hidden" name="subject_tagger_group_id" value="1">
    <input id="input-tag3" type="text" value="[[TaggerGetTags? &groups=`3` &rowTpl=`tag_tpl`]]" name="topic_tags">
    <input type="hidden" name="topic_tagger_group_id" value="3">
    <input id="input-tag4" type="text" value="[[TaggerGetTags? &groups=`4` &rowTpl=`tag_tpl`]]" name="grade_tags">
    <input type="text" name="name" value="[[!+fi.name]]">
    <input type="email" name="email" value="[[!+fi.email]]">
    <input name="submittheexampleform" type="submit" value="Send">
</form>

*/




// Set the logging level to ERROR and log target to FILE
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');

// Set default values for resource properties
$defaultParentId = 290; // Default parent ID where the resource will be saved
$defaultPublished = 0; // Default published status (0 = unpublished)
$defaultTemplate = 2; // Default template ID
$defaultUser = 732; // Default user ID if no user is logged in

// Create a new resource object
$doc = $modx->newObject('modResource');

// Determine the user ID, using the logged-in user or default if not available
$userId = $modx->user->get('id') ? $modx->user->get('id') : $defaultUser;
$doc->set('createdby', $userId); // Set the creator of the resource

// Retrieve all form field values
$allFormFields = $hook->getValues();

// Set core fields for the resource
foreach ($allFormFields as $field => $value) {
    if (in_array($field, ['pagetitle', 'content'])) {
        $doc->set($field, $value); // Set the page title and content
    }
}

// Set additional properties for the resource
$doc->set('parent', $defaultParentId); // Set the parent ID
$doc->set('alias', time()); // Set a unique alias using the current timestamp
$doc->set('template', $defaultTemplate); // Set the template ID
$doc->set('published', $defaultPublished); // Set the published status

// Attempt to save the resource and log an error if it fails
if (!$doc->save()) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Failed to save resource');
    return false; // Exit the script if saving fails
}

$resourceId = $doc->get('id'); // Get the ID of the newly saved resource
// $modx->log(modX::LOG_LEVEL_ERROR, "Resource saved with ID: $resourceId"); //comment out for debugging

// Loop through form fields to save template variables (TVs)
foreach ($allFormFields as $field => $value) {
    if ($tv = $modx->getObject('modTemplateVar', ['name' => $field])) {
        if (is_array($value)) {
            $value = implode('||', $value); // Convert array values to a string
        }
        $tv->setValue($resourceId, $value); // Set the TV value for the resource
        if (!$tv->save()) {
            $modx->log(modX::LOG_LEVEL_ERROR, "Failed to save TV: $field for Resource ID: $resourceId");
        }
    }
}

// Define tag fields and their corresponding group IDs these correspond to your input details see html
$tagFields = [
    'subject_tags' => 'subject_tagger_group_id',
    'topic_tags' => 'topic_tagger_group_id',
    'grade_tags' => 'grade_tagger_group_id',
    'material_type_tags' => 'material_type_tagger_group_id',
    'editor_tags' => 'editor_tagger_group_id',
    'language_tags' => 'language_tagger_group_id',
];

// Process each tagger field
foreach ($tagFields as $tagField => $groupField) {
    if (!empty($allFormFields[$tagField]) && !empty($allFormFields[$groupField])) {
        $tags = explode(',', $allFormFields[$tagField]); // Split tags into an array
        $taggerGroupId = (int) $allFormFields[$groupField]; // Get the tagger group ID

       // $modx->log(modX::LOG_LEVEL_ERROR, "Processing tags for $tagField in group $taggerGroupId"); comment out for debugging

        foreach ($tags as $tag) {
            $tag = trim($tag); // Remove whitespace from the tag
            if (!empty($tag)) {
                // Check if the tag already exists in the database
                $tagObject = $modx->getObject('Tagger\Model\TaggerTag', [
                    'tag' => $tag,
                    'group' => $taggerGroupId,
                ]);

                // If the tag does not exist, create a new tag object
                if (!$tagObject) {
                    $tagObject = $modx->newObject('Tagger\Model\TaggerTag');
                    $tagObject->set('tag', $tag);
                    $tagObject->set('group', $taggerGroupId);
                    if (!$tagObject->save()) {
                        $modx->log(modX::LOG_LEVEL_ERROR, "Failed to create Tag: $tag in Group: $taggerGroupId");
                        continue; // Skip linking if tag creation failed
                    }
                }

                // Get the ID of the tag
                $tagId = $tagObject->get('id');

                // Check if a link between the tag and resource already exists
                $tagResourceLink = $modx->getObject('Tagger\Model\TaggerTagResource', [
                    'tag' => $tagId,
                    'resource' => $resourceId,
                ]);

                // If no link exists, create a new link
                if (!$tagResourceLink) {
                    $tagResourceLink = $modx->newObject('Tagger\Model\TaggerTagResource');
                    $tagResourceLink->set('tag', $tagId);
                    $tagResourceLink->set('resource', $resourceId);

                    // Attempt to save the link and log the result
                    if (!$tagResourceLink->save()) {
                        $modx->log(modX::LOG_LEVEL_ERROR, "Failed to link Tag: $tagId to Resource ID: $resourceId");
                    } else {
                      //  $modx->log(modX::LOG_LEVEL_ERROR, "Linked Tag: $tagId to Resource ID: $resourceId"); comment out for debug
                    }
                }
            }
        }
    }
}

return true; // Return true to indicate successful execution
