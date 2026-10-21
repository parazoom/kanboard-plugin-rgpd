<?php

// Public boards are rendered without the application layout, so the footer
// hook is not available here. The template previously in place is included
// untouched and the footer is appended below it.
include $this->rgpd->getOverriddenTemplateFile('board/view_public');

echo $this->render('rgpd:layout/footer');
