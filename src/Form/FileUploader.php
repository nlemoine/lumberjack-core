<?php

namespace Rareloop\Lumberjack\Form;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

class FileUploader
{
    private $targetDirectory;

    private $slugger;

    public function __construct($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = new AsciiSlugger();
    }

    /**
     * Upload a file
     *
     * @throws FileException
     * @return string
     */
    public function upload(UploadedFile $file)
    {
        $originalFilename = \pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . \uniqid() . '.' . $file->guessExtension();

        $file->move($this->getTargetDirectory(), $fileName);

        return $this->targetDirectory . '/' . $fileName;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }

    public function setTargetDirectory($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
        return $this;
    }
}
