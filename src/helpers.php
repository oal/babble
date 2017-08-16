<?php

function absPath($path)
{
    // Current working directory is "public/", so step out once.
    return getcwd() . '/../' . $path;
}