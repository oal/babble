<?php

function absPath($path): string
{
    // Current working directory is "public/", so step out once.
    return getcwd() . '/../' . $path;
}