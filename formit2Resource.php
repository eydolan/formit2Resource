<?php
$doc = $modx->newObject('modResource');
$doc->set('createdby', $modx->user->get('id'));

$allFormFields = $hook->getValues(); 

// Set core fields
foreach ($allFormFields as $field => $value) {
    if (in_array($field, ['pagetitle', 'content'])) {
        $doc->set($field, $value);
    }
}

// Set other resource properties
$doc->set('parent', 290);
$doc->set('alias', time());
$doc->set('template', '5'); 
$doc->set('published', '1');

// Save the resource first to generate an ID
if ($doc->save() === false) {
    return $modx->log(modX::LOG_LEVEL_ERROR, 'Failed to save resource');
}

// Check if the resource ID is generated
$resourceId = $doc->get('id');
if (!$resourceId) {
    return $modx->log(modX::LOG_LEVEL_ERROR, 'Resource ID not generated');
}

// Debug to ensure resource is saved before proceeding with TVs
$modx->log(modX::LOG_LEVEL_INFO, 'Resource created with ID: ' . $resourceId);

// Loop to save template variables (TVs)
foreach ($allFormFields as $field => $value) {
    if ($tv = $modx->getObject('modTemplateVar', ['name' => $field])) {
        if (is_array($value)) {
            $value = implode('||', $value);
        }
        $tv->setValue($resourceId, $value);
        $tv->save();
        $modx->log(modX::LOG_LEVEL_INFO, "Set TV {$field} to {$value} on resource {$resourceId}");
    } else {
        $modx->log(modX::LOG_LEVEL_ERROR, "Template Variable {$field} not found");
    }
}

// Manually set any other TV values directly if they aren't in $allFormFields
$templateVars = [
    'material' => $materialid,
    'userreview' => $userid,
    'rating_1' => $rating1val,
    'review_1' => $txtrate1,
    'rating_2' => $rating2val,
    'review_2' => $txtrate2,
    'rating_3' => $rating3val,
    'review_3' => $txtrate3,
    'rating_4' => $rating4val,
    'review_4' => $txtrate4,
    'rating_5' => $rating5val,
    'review_5' => $txtrate5,
    'rating_6' => $rating6val,
    'review_6' => $txtrate6,
    'rating_7' => $rating7val,
    'review_7' => $txtrate7,
    'rating_8' => $rating8val,
    'review_8' => $txtrate8
];

foreach ($templateVars as $tvName => $tvValue) {
    if ($tv = $modx->getObject('modTemplateVar', ['name' => $tvName])) {
        $tv->setValue($resourceId, $tvValue);
        $tv->save();
        $modx->log(modX::LOG_LEVEL_INFO, "Manually set TV {$tvName} to {$tvValue} on resource {$resourceId}");
    } else {
        $modx->log(modX::LOG_LEVEL_ERROR, "Template Variable {$tvName} not found for manual setting");
    }
}

return true;
?>
