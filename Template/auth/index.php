<?php

// The login page is rendered without the application layout, so the footer
// hook is not available here. The template previously in place is included
// untouched and the footer is appended below it, outside of the login form,
// so that it sits at the bottom of the page like on every other view.
include $this->rgpd->getOverriddenTemplateFile('auth/index');

echo $this->render('rgpd:layout/footer');
