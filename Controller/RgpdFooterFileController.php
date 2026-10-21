<?php

namespace Kanboard\Plugin\Rgpd\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Core\Controller\PageNotFoundException;
use Kanboard\Core\ObjectStorage\ObjectStorageException;

/**
 * Public viewer for the footer documents
 *
 * Those documents are legal notices: they must stay reachable from the
 * login page and from the public boards, so this controller is public.
 *
 * @package  Rgpd\Controller
 */
class RgpdFooterFileController extends BaseController
{
    /**
     * Display a PDF document in the browser
     *
     * @access public
     * @throws PageNotFoundException
     */
    public function show()
    {
        $item = $this->footerItemModel->getFileById($this->request->getIntegerParam('item_id'));

        if (empty($item)) {
            throw PageNotFoundException::getInstance()->withoutLayout();
        }

        $etag = md5($item['file_path']);

        if ($this->request->getHeader('If-None-Match') === '"'.$etag.'"') {
            $this->response->status(304);
            return;
        }

        try {
            $this->response->withContentType('application/pdf');
            $this->response->withHeader('Content-Disposition', 'inline; filename="'.$this->getSafeFilename($item).'"');
            $this->response->withCache(86400, $etag);
            $this->response->send();
            $this->objectStorage->output($item['file_path']);
        } catch (ObjectStorageException $e) {
            $this->logger->error(__METHOD__.': '.$e->getMessage());
        }
    }

    /**
     * Build a filename safe to put in a header
     *
     * @access protected
     * @param  array $item
     * @return string
     */
    protected function getSafeFilename(array $item)
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $item['file_name']);

        return $filename === '' ? 'document.pdf' : $filename;
    }
}
