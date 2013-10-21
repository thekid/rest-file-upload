<?php namespace de\thekid\demo\rest;

use io\collections\FileCollection;
use io\collections\IOCollection;
use io\collections\iterate\FilteredIOCollectionIterator;
use io\collections\iterate\CollectionFilter;
use io\collections\iterate\NegationOfFilter;
use io\File;
use util\Properties;
use util\MimeType;
use util\NoSuchElementException;
use lang\ElementNotFoundException;
use webservices\rest\srv\StreamingOutput;

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
   * Get a single file
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
}