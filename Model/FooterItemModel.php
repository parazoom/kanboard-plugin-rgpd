<?php

namespace Kanboard\Plugin\Rgpd\Model;

use Exception;
use Kanboard\Core\Base;
use Kanboard\Core\ObjectStorage\ObjectStorageException;

/**
 * Footer Item Model
 *
 * A footer item is either an external link or an uploaded PDF document.
 * Both kinds share the same table so that they can be sorted together.
 *
 * @package  Rgpd\Model
 */
class FooterItemModel extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE = 'rgpd_footer_items';

    /**
     * Item types
     *
     * @var string
     */
    const TYPE_URL = 'url';
    const TYPE_FILE = 'file';

    /**
     * Object storage path prefix
     *
     * @var string
     */
    const PATH_PREFIX = 'rgpd';

    /**
     * Lowest and highest accepted weights
     *
     * @var integer
     */
    const MIN_WEIGHT = 1;
    const MAX_WEIGHT = 10;

    /**
     * Get the list of accepted weights
     *
     * @access public
     * @return array
     */
    public function getWeightList()
    {
        return array_combine(
            range(self::MIN_WEIGHT, self::MAX_WEIGHT),
            range(self::MIN_WEIGHT, self::MAX_WEIGHT)
        );
    }

    /**
     * Get all items sorted by weight
     *
     * @access public
     * @return array
     */
    public function getAll()
    {
        return $this->db
            ->table(self::TABLE)
            ->asc('weight')
            ->asc('title')
            ->findAll();
    }

    /**
     * Get one item
     *
     * @access public
     * @param  integer $item_id
     * @return array|null
     */
    public function getById($item_id)
    {
        return $this->db->table(self::TABLE)->eq('id', $item_id)->findOne();
    }

    /**
     * Get one PDF item
     *
     * @access public
     * @param  integer $item_id
     * @return array|null
     */
    public function getFileById($item_id)
    {
        return $this->db
            ->table(self::TABLE)
            ->eq('id', $item_id)
            ->eq('type', self::TYPE_FILE)
            ->neq('file_path', '')
            ->findOne();
    }

    /**
     * Create an external link
     *
     * @access public
     * @param  array $values
     * @return bool|integer
     */
    public function createLink(array $values)
    {
        $result = $this->db->table(self::TABLE)->insert(array(
            'title' => substr($values['title'], 0, 255),
            'type' => self::TYPE_URL,
            'url' => $values['url'],
            'weight' => (int) $values['weight'],
            'date_creation' => time(),
            'user_id' => $this->userSession->getId() ?: 0,
        ));

        return $result ? (int) $this->db->getLastId() : false;
    }

    /**
     * Update an external link
     *
     * @access public
     * @param  array $values
     * @return bool
     */
    public function updateLink(array $values)
    {
        return $this->db->table(self::TABLE)->eq('id', $values['id'])->update(array(
            'title' => substr($values['title'], 0, 255),
            'url' => $values['url'],
            'weight' => (int) $values['weight'],
        ));
    }

    /**
     * Create a PDF item and attach the uploaded document
     *
     * @access public
     * @param  array $values
     * @param  array $file    Single entry of $_FILES
     * @return bool|integer
     */
    public function createFile(array $values, array $file)
    {
        $result = $this->db->table(self::TABLE)->insert(array(
            'title' => substr($values['title'], 0, 255),
            'type' => self::TYPE_FILE,
            'weight' => (int) $values['weight'],
            'date_creation' => time(),
            'user_id' => $this->userSession->getId() ?: 0,
        ));

        if (! $result) {
            return false;
        }

        $item_id = (int) $this->db->getLastId();

        if (! $this->attachFile($item_id, $file)) {
            $this->db->table(self::TABLE)->eq('id', $item_id)->remove();
            return false;
        }

        return $item_id;
    }

    /**
     * Update a PDF item, the document itself being optional
     *
     * @access public
     * @param  array $values
     * @param  array $file    Single entry of $_FILES, empty to keep the current document
     * @return bool
     */
    public function updateFile(array $values, array $file = array())
    {
        $result = $this->db->table(self::TABLE)->eq('id', $values['id'])->update(array(
            'title' => substr($values['title'], 0, 255),
            'weight' => (int) $values['weight'],
        ));

        if ($result && ! empty($file) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $item = $this->getById($values['id']);
            $previous_path = $item['file_path'];

            if (! $this->attachFile($values['id'], $file)) {
                return false;
            }

            $this->removeFileFromStorage($previous_path);
        }

        return $result;
    }

    /**
     * Remove an item and its document
     *
     * @access public
     * @param  integer $item_id
     * @return bool
     */
    public function remove($item_id)
    {
        $item = $this->getById($item_id);

        if (empty($item)) {
            return false;
        }

        $this->removeFileFromStorage($item['file_path']);

        return $this->db->table(self::TABLE)->eq('id', $item_id)->remove();
    }

    /**
     * Store an uploaded document and link it to an item
     *
     * @access protected
     * @param  integer $item_id
     * @param  array   $file
     * @return bool
     */
    protected function attachFile($item_id, array $file)
    {
        try {
            if (empty($file) || $file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0) {
                throw new Exception('Document not uploaded: '.var_export(isset($file['error']) ? $file['error'] : null, true));
            }

            if (! $this->isPdf($file['tmp_name'], $file['name'])) {
                throw new Exception('Uploaded document is not a PDF file');
            }

            $path = self::PATH_PREFIX.DIRECTORY_SEPARATOR.$item_id.DIRECTORY_SEPARATOR.hash('sha1', $file['name'].microtime(true)).'.pdf';
            $this->objectStorage->moveUploadedFile($file['tmp_name'], $path);

            return $this->db->table(self::TABLE)->eq('id', $item_id)->update(array(
                'file_name' => substr(basename($file['name']), 0, 255),
                'file_path' => $path,
                'file_size' => (int) $file['size'],
            ));
        } catch (Exception $e) {
            $this->logger->error(__METHOD__.': '.$e->getMessage());
            return false;
        }
    }

    /**
     * Delete a document from the object storage
     *
     * @access protected
     * @param  string $path
     */
    protected function removeFileFromStorage($path)
    {
        if (empty($path)) {
            return;
        }

        try {
            $this->objectStorage->remove($path);
        } catch (ObjectStorageException $e) {
            $this->logger->error(__METHOD__.': '.$e->getMessage());
        }
    }

    /**
     * Check that a URL uses a browsable scheme
     *
     * @access public
     * @param  string $url
     * @return bool
     */
    public function hasWebScheme($url)
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $scheme === 'http' || $scheme === 'https';
    }

    /**
     * Check that an uploaded document really is a PDF file
     *
     * The extension alone is not trusted: the file signature is checked too.
     *
     * @access public
     * @param  string $filename   Path of the file on the disk
     * @param  string $originalName
     * @return bool
     */
    public function isPdf($filename, $originalName)
    {
        if (strtolower(get_file_extension($originalName)) !== 'pdf') {
            return false;
        }

        $handle = @fopen($filename, 'rb');

        if ($handle === false) {
            return false;
        }

        $signature = fread($handle, 5);
        fclose($handle);

        return $signature === '%PDF-';
    }
}
