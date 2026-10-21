<?php

namespace Kanboard\Plugin\Rgpd\Validator;

use Kanboard\Plugin\Rgpd\Model\FooterItemModel;
use Kanboard\Validator\BaseValidator;
use SimpleValidator\Validator;
use SimpleValidator\Validators;

/**
 * Footer Item Validator
 *
 * @package  Rgpd\Validator
 */
class FooterItemValidator extends BaseValidator
{
    /**
     * Validate an external link
     *
     * @access public
     * @param  array $values
     * @return array  $valid, $errors
     */
    public function validateLink(array $values)
    {
        $v = new Validator($values, array_merge(
            $this->commonRules(),
            array(
                new Validators\Required('url', t('Field required')),
                new Validators\MaxLength('url', t('The maximum length is %d characters', 2000), 2000),
                new Validators\URL('url', t('This URL is invalid')),
            )
        ));

        $valid = $v->execute();
        $errors = $v->getErrors();

        if ($valid && ! $this->footerItemModel->hasWebScheme($values['url'])) {
            $valid = false;
            $errors['url'] = array(t('Only http:// and https:// links are allowed'));
        }

        return array($valid, $errors);
    }

    /**
     * Validate a PDF document
     *
     * @access public
     * @param  array $values
     * @param  array $file            Single entry of $_FILES
     * @param  bool  $file_required   False when editing an existing document
     * @return array  $valid, $errors
     */
    public function validateFile(array $values, array $file, $file_required = true)
    {
        $v = new Validator($values, $this->commonRules());

        $valid = $v->execute();
        $errors = $v->getErrors();

        $has_upload = ! empty($file) && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE;

        if (! $has_upload) {
            if ($file_required) {
                $valid = false;
                $errors['document'] = array(t('Field required'));
            }

            return array($valid, $errors);
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $valid = false;
            $errors['document'] = array($this->getUploadErrorMessage($file['error']));
        } elseif (! $this->footerItemModel->isPdf($file['tmp_name'], $file['name'])) {
            $valid = false;
            $errors['document'] = array(t('Only PDF documents are allowed'));
        }

        return array($valid, $errors);
    }

    /**
     * Rules shared by both kinds of items
     *
     * @access protected
     * @return array
     */
    protected function commonRules()
    {
        return array(
            new Validators\Required('title', t('Field required')),
            new Validators\MaxLength('title', t('The maximum length is %d characters', 255), 255),
            new Validators\Required('weight', t('Field required')),
            new Validators\Integer('weight', t('This value must be an integer')),
            new Validators\Range(
                'weight',
                t('This value must be in the range %d to %d', FooterItemModel::MIN_WEIGHT, FooterItemModel::MAX_WEIGHT),
                FooterItemModel::MIN_WEIGHT,
                FooterItemModel::MAX_WEIGHT
            ),
        );
    }

    /**
     * Translate a PHP upload error code
     *
     * @access protected
     * @param  integer $error
     * @return string
     */
    protected function getUploadErrorMessage($error)
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return t('The maximum allowed file size is %sB.', $this->helper->text->bytes(get_upload_max_size()));
            default:
                return t('Unable to upload this file.');
        }
    }
}
