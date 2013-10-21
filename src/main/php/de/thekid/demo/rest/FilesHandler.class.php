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
use webservices\rest\srv\StreamingInput;
use webservices\rest\srv\Uploads;
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
   * @return  webservices.rest.srv.Output
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
   * @param   webservices.rest.srv.Input
   * @return  webservices.rest.srv.Output
   */
  #[@webmethod(verb= 'POST', accepts= 'multipart/form-data')]
  public function newFile(Uploads $uploads) {
    $file= $uploads->eachNamed('file')[0];
    if ($this->base->findElement($file->getName())) {
      throw new IllegalArgumentException('File "'.$file->getName().'"" already exists');
    }

    // Copy /tmp-File
    $t= new StreamTransfer(
      $file->getInputStream(),
      $this->base->newElement($file->getName())->getOutputStream()
    );
    $t->transferAll();
    $t->close();

    return Response::created($this->getClass()->getAnnotation('webservice', 'path').$file->getName());
  }

  /**
   * Upload a single file
   *
   * @param   string $name
   * @param   webservices.rest.srv.Input $file
   * @return  void
   */
  #[@webmethod(verb= 'PUT', path= '/{name}')]
  public function changeFile($name, StreamingInput $file) {
    if (!($element= $this->base->findElement($name))) {
      throw new IllegalArgumentException('File "'.$name.'" does not exist');
    }

    // Transfer
    $t= new StreamTransfer(
      $file->getInputStream(),
      $this->base->newElement($name)->getOutputStream()
    );
    $t->transferAll();
    $t->close();
  }


  /**
   * Delete a file
   *
   * @param   string $name
   * @return  void
   */
  #[@webmethod(verb= 'DELETE', path= '/{name}')]
  public function removeFile($name) {
    if (!$this->base->findElement($name)) {
      throw new ElementNotFoundException('File "'.$name.'"" does not exist');
    }

    $this->base->removeElement($name);
  }
}