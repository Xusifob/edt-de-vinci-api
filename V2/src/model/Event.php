<?php

class Event implements JsonSerializable
{

    private $description;

    private $title;

    private $start;

    private $end;

    private $location;

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }



    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }



    /**
     * @return mixed
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param mixed $start
     */
    public function setStart($start)
    {
        $this->start = date('c', strtotime($start));
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param mixed $end
     */
    public function setEnd($end)
    {

        $this->end = date('c', strtotime($end));
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param mixed $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }


    /**
     * @return mixed
     */
    public function __toString()
    {
        return $this->getTitle();
    }


    public function jsonSerialize() {
        return [
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'start' => $this->getStart(),
            'end' => $this->getEnd(),
            'location' => $this->getLocation(),
        ];
    }



}