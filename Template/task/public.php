<?php

// Same as the public board: the template previously in place is included
// untouched and the footer is appended below it.
include $this->rgpd->getOverriddenTemplateFile('task/public');

echo $this->render('rgpd:layout/footer');
