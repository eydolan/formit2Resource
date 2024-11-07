<?php
/*
quick hook for formit. save as a snippet and call in formit hook
make sure you input field correspond to the TV and fieldnames in the resource
there might be security issues here that still need to be sorted
*/

$doc = $modx->newObject('modResource');
$doc->set('createdby', $modx->user->get('id'));

$allFormFields = $hook->getValues(); 

// Set core fields
foreach ($allFormFields as $field => $value) {
    // Assuming only core fields like pagetitle and content should be directly set on the resource
    if (in_array($field, ['pagetitle', 'content'])) {
        $doc->set($field, $value);
    }
}

// Set other resource properties
$doc->set('parent', 31);
$doc->set('alias', time());
$doc->set('template', '5'); 
$doc->set('published', '1');

$doc->save(); // Save the resource first to generate an ID

// Loop to save template variables (TVs)
foreach ($allFormFields as $field => $value) {
    // Ensure this is a TV and not a core field
    if ($tv = $modx->getObject('modTemplateVar', ['name' => $field])) {
        // Handle checkbox/multi-select arrays
        if (is_array($value)) {
            $value = implode('||', $value);
        }
        $tv->setValue($doc->get('id'), $value); // Set the TV value for this resource ID
        $tv->save();
    }
}

// Manually set any other TV values directly if they aren't in $allFormFields
/*
$templateVars = [
    'material' => $materialid
    
];*/

foreach ($templateVars as $tvName => $tvValue) {
    if ($tv = $modx->getObject('modTemplateVar', ['name' => $tvName])) {
        $tv->setValue($doc->get('id'), $tvValue);
        $tv->save();
    }
}

return true;
