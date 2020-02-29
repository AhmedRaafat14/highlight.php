<?php

/* Copyright (c) 2019 Geert Bergman (geert@scrivo.nl), highlight.php
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 3. Neither the name of "highlight.js", "highlight.php", nor the names of its
 *    contributors may be used to endorse or promote products derived from this
 *    software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace HighlightUtilities;

require_once __DIR__ . '/_internals.php';
require_once __DIR__ . '/_themeColors.php';

/**
 * Get a list of available stylesheets.
 *
 * By default, a list of filenames without the `.css` extension will be returned.
 * This can be configured with the `$filePaths` argument.
 *
 * @api
 *
 * @since 9.15.8.1
 *
 * @param bool $filePaths Return absolute paths to stylesheets instead
 *
 * @return string[]
 */
function getAvailableStyleSheets($filePaths = false)
{
    $results = array();

    $folder = getStyleSheetFolder();
    $dh = @dir($folder);

    if ($dh) {
        while (($entry = $dh->read()) !== false) {
            if (substr($entry, -4, 4) !== ".css") {
                continue;
            }

            if ($filePaths) {
                $results[] = implode(DIRECTORY_SEPARATOR, array($folder, $entry));
            } else {
                $results[] = basename($entry, ".css");
            }
        }

        $dh->close();
    }

    return $results;
}

/**
 * Get the hexadecimal color code used for the background of a given theme.
 *
 * @api
 *
 * @since 9.18.1.1
 *
 * @param string $name The stylesheet name (with or without the extension)
 *
 * @throws \DomainException when no stylesheet with this name exists
 *
 * @return string A hexadecimal representation of the background (includes the '#')
 */
function getThemeBackgroundColor($name)
{
    return _getThemeBackgroundColor(getNoCssExtension($name));
}

/**
 * Get the contents of the given stylesheet.
 *
 * @api
 *
 * @since 9.15.8.1
 *
 * @param string $name The stylesheet name (with or without the extension)
 *
 * @throws \DomainException when the no stylesheet with this name exists
 *
 * @return false|string The CSS content of the stylesheet or FALSE when
 *                      the stylesheet content could be read
 */
function getStyleSheet($name)
{
    $path = getStyleSheetPath($name);

    return file_get_contents($path);
}

/**
 * Get the absolute path to the folder containing the stylesheets distributed in this package.
 *
 * @api
 *
 * @since 9.15.8.1
 *
 * @return string An absolute path to the folder
 */
function getStyleSheetFolder()
{
    $paths = array(__DIR__, '..', 'styles');

    return implode(DIRECTORY_SEPARATOR, $paths);
}

/**
 * Get the absolute path to a given stylesheet distributed in this package.
 *
 * @api
 *
 * @since 9.15.8.1
 *
 * @param string $name The stylesheet name (with or without the extension)
 *
 * @throws \DomainException when the no stylesheet with this name exists
 *
 * @return string The absolute path to the stylesheet with the given name
 */
function getStyleSheetPath($name)
{
    $name = getNoCssExtension($name);
    $path = implode(DIRECTORY_SEPARATOR, array(getStyleSheetFolder(), $name)) . ".css";

    if (!file_exists($path)) {
        throw new \DomainException("There is no stylesheet with by the name of '$name'.");
    }

    return $path;
}

/**
 * Convert the HTML generated by Highlighter and split it up into an array of lines.
 *
 * @api
 *
 * @since 9.15.6.1
 *
 * @param string $html An HTML string generated by `Highlighter::highlight()`
 *
 * @throws \RuntimeException         when the DOM extension is not available
 * @throws \UnexpectedValueException when the given HTML could not be parsed
 *
 * @return string[]|false An array of lines of code as strings. False if an error occurred in splitting up by lines
 */
function splitCodeIntoArray($html)
{
    if (!extension_loaded("dom")) {
        throw new \RuntimeException("The DOM extension is not loaded but is required.");
    }

    $dom = new \DOMDocument();

    if (!$dom->loadHTML($html)) {
        throw new \UnexpectedValueException("The given HTML could not be parsed correctly.");
    }

    $spans = $dom->getElementsByTagName("span");

    /** @var \DOMElement $span */
    foreach ($spans as $span) {
        $classes = $span->getAttribute("class");
        $renderedSpan = $dom->saveHTML($span);

        if (preg_match('/\R/', $renderedSpan)) {
            $finished = preg_replace(
                '/\R/',
                sprintf('</span>%s<span class="%s">', PHP_EOL, $classes),
                $renderedSpan
            );
            $html = str_replace($renderedSpan, $finished, $html);
        }
    }

    return preg_split('/\R/', $html);
}
