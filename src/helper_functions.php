<?php

// Function to remove folders and files
function recursive_rmdir( $dir )
{
    if ( is_dir( $dir ) )
    {
        $files = scandir( $dir );

        foreach ( $files as $file )
        {
            if ( $file == "." || $file == ".." ) continue;

            recursive_rmdir( "{$dir}/{$file}" );
        }

        rmdir( $dir );
    }
    elseif ( file_exists( $dir ) )
    {
        unlink( $dir );
    }
}

// Function to Copy folders and files
function recursive_copy( $src, $dst )
{
    if ( file_exists( $dst ) )
    {
        recursive_rmdir( $dst );
    }

    if ( is_dir( $src ) )
    {
        mkdir( $dst );
        $files = scandir( $src );

        foreach ( $files as $file )
        {
            if ( $file == "." || $file == ".." ) continue;

            recursive_copy( "{$src}/{$file}", "{$dst}/{$file}" );
        }
    }
    elseif ( file_exists( $src ) )
    {
        copy( $src, $dst );
    }
}
