<?php namespace de\thekid\demo\rest;

use io\collections\FileCollection;
use io\collections\IOCollection;
use io\collections\iterate\FilteredIOCollectionIterator;
use io\collections\iterate\CollectionFilter;
use io\collections\iterate\NegationOfFilter;
use io\streams\StreamTransfer;
use io\File;
use util\Properties;
use util\MimeType;
use util\NoSuchElementException;
use lang\ElementNotFoundException;
use lang\IllegalArgumentException;
use webservices\rest\srv\StreamingOutput;
use webservices\rest\srv\Response;

#[@webservice(path= '/')]
class FilesHandler extends \lang\Object {

  #[@type('io.collections.RandomCollectionAccess')]
  protected $base= null;

  #[@inject(name= 'storage')]
  public function configure(Properties $conf) {
    $this->base= new FileCollection($conf->readString('storage', 'base'));
  }

  /**
   * Lists all files
   *
   * @return  var[]
   */
  #[@webmethod(verb= 'GET')]
  public function listFiles() {
    $entries= [];
    $noFolders= new NegationOfFilter(new CollectionFilter());
    foreach (new FilteredIOCollectionIterator($this->base, $noFolders) as $element) {
      $entries[]= array(
        'name'     => basename($element->getURI()),
        'mime'     => MimeType::getByFilename($element->getURI()),
        'size'     => $element->getSize(),
        'modified' => $element->lastModified()
      );
    }
    return $entries;
  }

  /**
   * Download a single file
   *
   * @param   string $name
   * @return  webservices.rest.srv.StreamingOutput
   */
  #[@webmethod(verb= 'GET', path= '/{name}')]
  public function getFile($name) {
    try {
      return StreamingOutput::of($this->base->getElement($name));
    } catch (NoSuchElementException $e) {
      throw new ElementNotFoundException('No file called "'.$name.'"');
    }
  }

  /**
   * Upload a single file
   *
   * @return  webservices.rest.srv.Response
   */
  #[@webmethod(verb= 'POST', accepts= 'multipart/form-data'), @$file: param('file')]
  public function uploadFile($file) {
    if ($this->base->findElement($file['name'])) {
      throw new IllegalArgumentException('File "'.$file['name'].'"" already exists');
    }

    // Copy /tmp-File
    $t= new StreamTransfer(
      create(new File($file['tmp_name']))->getInputStream(),
      $this->base->newElement($file['name'])->getOutputStream()
    );
    $t->transferAll();
    $t->close();

    return Response::created($this->getClass()->getAnnotation('webservice', 'path').$file['name']);
  }

  /**
   * Delete a file
   *
   * @param   string $name
   * @return  webservices.rest.srv.Response
   */
  #[@webmethod(verb= 'DELETE', path= '/{name}')]
  public function removeFile($name) {
    if (!$this->base->findElement($name)) {
      throw new ElementNotFoundException('File "'.$name.'"" does not exist');
    }

    $this->base->removeElement($name);
    return Response::noContent();
  }
}