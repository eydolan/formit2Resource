<?php
/*
$doc = $modx->newObject('modResource');
$doc->set('createdby', $modx->user->get('id'));
 
$allFormFields = $hook->getValues(); 
foreach ($allFormFields as $field=>$value)
{
   $doc->set($field, $value);
}

$doc->set('parent', 290);
$doc->set('alias', time());
$doc->set('template', '5'); 
$doc->set('published', '1');
$doc->set('pagetitle', $pagetitle);
$doc->set('material', $materialid);
$doc->set('userreview', $userid);
$doc->set('rating_1', $rating1val);
$doc->set('review_1', $txtrate1);
$doc->set('rating_2', $rating1val);
$doc->set('review_2', $txtrate1);
$doc->set('rating_3', $rating1val);
$doc->set('review_3', $txtrate1);
$doc->set('rating_4', $rating1val);
$doc->set('review_4', $txtrate1);
$doc->set('rating_5', $rating1val);
$doc->set('review_5', $txtrate1);
$doc->set('rating_6', $rating1val);
$doc->set('review_6', $txtrate1);
$doc->set('rating_7', $rating1val);
$doc->set('review_7', $txtrate1);
$doc->set('rating_8', $rating1val);
$doc->set('review_8', $txtrate1);
$doc->save();
 
foreach ($allFormFields as $field=>$value)
{
    if ($tv = $modx->getObject('modTemplateVar', array ('name'=>$field)))
    {

        if (is_array($value)) {
            $featureInsert = array();
            while (list($featureValue, $featureItem) = each($value)) {
                $featureInsert[count($featureInsert)] = $featureItem;
                }
            $value = implode('||',$featureInsert);
            }   
            
        $tv->setValue($doc->get('id'), $value);

        $tv->save();
    }
}
 
return true;

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
$doc->set('parent', 290);
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
