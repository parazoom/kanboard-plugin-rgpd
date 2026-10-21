<?php

namespace Kanboard\Plugin\Rgpd\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Core\Controller\PageNotFoundException;
use Kanboard\Plugin\Rgpd\Model\FooterItemModel;

/**
 * Footer administration
 *
 * @package  Rgpd\Controller
 */
class RgpdFooterController extends BaseController
{
    /**
     * List the footer items
     *
     * @access public
     */
    public function show()
    {
        $this->response->html($this->helper->layout->config('rgpd:footer/show', array(
            'items' => $this->footerItemModel->getAll(),
            'title' => t('Settings').' &gt; '.t('Footer settings'),
        )));
    }

    /**
     * Display the link creation form
     *
     * @access public
     * @param  array $values
     * @param  array $errors
     */
    public function createLink(array $values = array(), array $errors = array())
    {
        if (empty($values)) {
            $values = array('weight' => 5);
        }

        $this->response->html($this->template->render('rgpd:footer/create_link', array(
            'values' => $values,
            'errors' => $errors,
            'weights' => $this->footerItemModel->getWeightList(),
        )));
    }

    /**
     * Save a new link
     *
     * @access public
     */
    public function saveLink()
    {
        $values = $this->request->getValues();
        list($valid, $errors) = $this->footerItemValidator->validateLink($values);

        if (! $valid) {
            return $this->createLink($values, $errors);
        }

        if ($this->footerItemModel->createLink($values)) {
            $this->flash->success(t('Link added successfully.'));
        } else {
            $this->flash->failure(t('Unable to save this link.'));
        }

        $this->response->redirect($this->helper->url->to('RgpdFooterController', 'show', array('plugin' => 'Rgpd')));
    }

    /**
     * Display the link edition form
     *
     * @access public
     * @param  array $values
     * @param  array $errors
     */
    public function editLink(array $values = array(), array $errors = array())
    {
        $item = $this->getItem(FooterItemModel::TYPE_URL);

        $this->response->html($this->template->render('rgpd:footer/edit_link', array(
            'values' => empty($values) ? $item : $values,
            'errors' => $errors,
            'item' => $item,
            'weights' => $this->footerItemModel->getWeightList(),
        )));
    }

    /**
     * Update a link
     *
     * @access public
     */
    public function updateLink()
    {
        $item = $this->getItem(FooterItemModel::TYPE_URL);
        $values = $this->request->getValues() + array('id' => $item['id']);
        list($valid, $errors) = $this->footerItemValidator->validateLink($values);

        if (! $valid) {
            return $this->editLink($values, $errors);
        }

        if ($this->footerItemModel->updateLink($values)) {
            $this->flash->success(t('Link updated successfully.'));
        } else {
            $this->flash->failure(t('Unable to update this link.'));
        }

        $this->response->redirect($this->helper->url->to('RgpdFooterController', 'show', array('plugin' => 'Rgpd')));
    }

    /**
     * Display the document upload form
     *
     * @access public
     * @param  array $values
     * @param  array $errors
     */
    public function createFile(array $values = array(), array $errors = array())
    {
        if (empty($values)) {
            $values = array('weight' => 5);
        }

        $this->response->html($this->template->render('rgpd:footer/create_file', array(
            'values' => $values,
            'errors' => $errors,
            'weights' => $this->footerItemModel->getWeightList(),
            'max_size' => get_upload_max_size(),
        )));
    }

    /**
     * Save a new document
     *
     * @access public
     */
    public function saveFile()
    {
        if ($this->isRequestTruncated()) {
            return $this->createFile(array('weight' => 5), array('document' => array($this->getMaxSizeMessage())));
        }

        $this->checkCSRFForm();

        $values = $this->request->getValues();
        $file = $this->getUploadedFile();
        list($valid, $errors) = $this->footerItemValidator->validateFile($values, $file);

        if (! $valid) {
            return $this->createFile($values, $errors);
        }

        if ($this->footerItemModel->createFile($values, $file)) {
            $this->flash->success(t('Document added successfully.'));
        } else {
            $this->flash->failure(t('Unable to upload this file.'));
        }

        $this->response->redirect($this->helper->url->to('RgpdFooterController', 'show', array('plugin' => 'Rgpd')));
    }

    /**
     * Display the document edition form
     *
     * @access public
     * @param  array $values
     * @param  array $errors
     */
    public function editFile(array $values = array(), array $errors = array())
    {
        $item = $this->getItem(FooterItemModel::TYPE_FILE);

        $this->response->html($this->template->render('rgpd:footer/edit_file', array(
            'values' => empty($values) ? $item : $values,
            'errors' => $errors,
            'item' => $item,
            'weights' => $this->footerItemModel->getWeightList(),
            'max_size' => get_upload_max_size(),
        )));
    }

    /**
     * Update a document
     *
     * @access public
     */
    public function updateFile()
    {
        if ($this->isRequestTruncated()) {
            return $this->editFile(array(), array('document' => array($this->getMaxSizeMessage())));
        }

        $this->checkCSRFForm();

        $item = $this->getItem(FooterItemModel::TYPE_FILE);
        $values = $this->request->getValues() + array('id' => $item['id']);
        $file = $this->getUploadedFile();
        list($valid, $errors) = $this->footerItemValidator->validateFile($values, $file, false);

        if (! $valid) {
            return $this->editFile($values, $errors);
        }

        if ($this->footerItemModel->updateFile($values, $file)) {
            $this->flash->success(t('Document updated successfully.'));
        } else {
            $this->flash->failure(t('Unable to update this document.'));
        }

        $this->response->redirect($this->helper->url->to('RgpdFooterController', 'show', array('plugin' => 'Rgpd')));
    }

    /**
     * Confirmation dialog before removing an item
     *
     * @access public
     */
    public function confirm()
    {
        $this->response->html($this->template->render('rgpd:footer/remove', array(
            'item' => $this->getItem(),
        )));
    }

    /**
     * Remove an item
     *
     * @access public
     */
    public function remove()
    {
        $this->checkCSRFParam();
        $item = $this->getItem();

        if ($this->footerItemModel->remove($item['id'])) {
            $this->flash->success(t('Item removed successfully.'));
        } else {
            $this->flash->failure(t('Unable to remove this item.'));
        }

        $this->response->redirect($this->helper->url->to('RgpdFooterController', 'show', array('plugin' => 'Rgpd')));
    }

    /**
     * Fetch the item of the current request
     *
     * @access protected
     * @param  string $type  Expected type, any type when empty
     * @return array
     * @throws PageNotFoundException
     */
    protected function getItem($type = '')
    {
        $item = $this->footerItemModel->getById($this->request->getIntegerParam('item_id'));

        if (empty($item) || ($type !== '' && $item['type'] !== $type)) {
            throw new PageNotFoundException();
        }

        return $item;
    }

    /**
     * Get the uploaded document of the current request
     *
     * @access protected
     * @return array
     */
    protected function getUploadedFile()
    {
        $file = $this->request->getFileInfo('document');

        return empty($file) ? array() : $file;
    }

    /**
     * Detect a request truncated by the PHP upload limits
     *
     * When the request body is larger than post_max_size, PHP discards both
     * the form values and the files, including the CSRF token.
     *
     * @access protected
     * @return bool
     */
    protected function isRequestTruncated()
    {
        $content_length = (int) $this->request->getServerVariable('CONTENT_LENGTH');

        return $content_length > 0 && empty($this->request->getRawFormValues());
    }

    /**
     * Get the message shown when a document exceeds the upload limits
     *
     * @access protected
     * @return string
     */
    protected function getMaxSizeMessage()
    {
        return t('The maximum allowed file size is %sB.', $this->helper->text->bytes(get_upload_max_size()));
    }
}
