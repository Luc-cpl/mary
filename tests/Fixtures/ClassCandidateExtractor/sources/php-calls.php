<?php

// Mary::classes('php-comment');
/** app('mary')->classes('doc-comment'); */
$documentation = 'Mary::classes("string-call")';
$override = 'override-class';
$primaryClass = 'primary-class';
$aliasClass = $primaryClass;
$shorthandClass = 'shorthand-class';
$matchClass = 'match-class';
$reusedClass = 'reused-class';
$wrappedClass = '$reusedClass';

Mary :: classes('facade-class shared');

app ( "mary" ) -> classes('app-class')
    ->add('chained-class shared')
    ->addRaw('consumer-class');

Mary::classes([
    'short-array',
    'conditional-array' => $enabled,
]);

Mary::classes(array(
    'legacy-array',
    'legacy-conditional' => $visible,
));

Mary::classes(($state === 'condition-value' ? 'active' : 'inactive'));
Mary::classes($outer ? ($inner ? 'nested-one' : 'nested-two') : 'nested-three');
Mary::classes($override ?? 'fallback');
Mary::classes($aliasClass);
Mary::classes($shorthandClass ?: 'shorthand-fallback');
Mary::classes(match ($state) {
    'condition-value' => $matchClass,
    default => 'match-fallback',
});
Mary::classes(resolveClass('nested-call-class'));
Mary::classes("$reusedClass $wrappedClass");
Mary::classes("$reusedClass {$reusedClass}");
