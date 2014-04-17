/**
 * layout.js - Core layout logic for the responsive ajax view. Provides an
 * implementation of a Justified Grid layout. Essentially:
 *   1) Take a set of images with a height that is at least some set max height.
 *   2) Calculate the scale factor ratio needed to bring each image down to the
 *      max height, and calculate the scaled widths of each image.
 *   3) Determine the images that can fit in the specified div's  width, while
 *      tracking the accumulated width, then calculate the ratio of the actual
 *      div width compared to the available/max div width.
 *   4) Use the ratio from 3 to resize the row and images to fill the
 *      entire row.
 *
 * @TODO: On-demand image loading/pagination.
 *        Refetch higher res images when viewport is scaled up.
 *
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 */
AnselLayout = {

    // Wrapper element that contains the gallery display:
    // <div id="anselLayoutMain">
    //   <div class="anselSizer"></div>
    //   <div class="anselRow"</div>
    //   <div class="anselRow"></div>
    // </div>
    container: 'anselLayoutMain',

    // CSS Selector of hidden row used to determine row width.
    hiddenDiv: 'anselSizer',

    // CSS selector of a single row.
    rowSelector: '.anselRow',

    // An array of image objects to be displayed.
    // [{id: xx, width_s: xx, height_s: xx }]
    images: [],

    // Used internally to calculate viewport size changes.
    lastWidth: 0,

    // Maximum height (in px) for thumbnails.
    maxHeight: 300,

    // Border width, in px.
    border: 4,

    reset: function()
    {
        $(AnselLayout.container).select(AnselLayout.rowSelector).each(function(r) {
            r.update();
        });
    },

    resize: function()
    {
        AnselLayout.lastWidth = $(AnselLayout.hiddenDiv).getWidth();
        $(AnselLayout.container).select(AnselLayout.rowSelector).each(function(r) {
            r.width = AnselLayout.lastWidth;
        });
        AnselLayout.process();
    },

    process: function()
    {
        var rows = $(AnselLayout.container).select(AnselLayout.rowSelector);
        var rowWidth = $(AnselLayout.hiddenDiv).getWidth(), scaledWidths = [], baseLine = 0,
        rowNum = 0;
        if (!AnselLayout.images.length) {
            return;
        }

        // Calculate scaled image heights
        // @TODO, request newly sized images for certain size thresholds.
        AnselLayout.images.each(function(im) {
            var wt = parseInt(im.width_s, 10);
            var ht = parseInt(im.height_s, 10);
            if (ht != AnselLayout.maxHeight) {
                wt = Math.floor(wt *  (AnselLayout.maxHeight / ht));
            }
            scaledWidths.push(wt);
        });

        while (rowNum++ < rows.length) {
            var d_row = rows[rowNum - 1];
            d_row.update();

            // number of images appearing in this row
            var c = 0;

            // total width of images in this row - including margins
            var tw = 0;

            // calculate width of images and number of images to view in this row.
            while (tw * 1.1 < rowWidth) {
                if (baseLine + c >= scaledWidths.length) {
                    break;
                }
                tw += scaledWidths[baseLine + c++] + AnselLayout.border * 2;
            }

            // Ratio of actual width of row to total width of images to be used.
            var r = rowWidth / tw;

            // image number being processed
            var i = 0;

            // reset total width to be total width of processed images
            tw = 0;

            // new height
            var newht = Math.floor(AnselLayout.maxHeight * r);
            while (i < c) {
                var photo = AnselLayout.images[baseLine + i];
                // Calculate new width based on ratio
                var wt = Math.floor(scaledWidths[baseLine + i] * r);
                // add to total width with margins
                tw += wt + AnselLayout.border * 2;
                // Create image, set src, width, height and margin
                (function() {
                    var img = new Element('img', {class: 'ansel-photo', src: photo.screen, width: wt, height: newht}).setStyle({margin: AnselLayout.border + "px"});
                    d_row.insert(img);
                })();
                i++;
            }

            // if total width is slightly smaller than
            // actual div width then add 1 to each
            // photo width till they match
            i = 0;
            while (tw < rowWidth) {
                var imgs = d_row.select('img:nth-child(' + (i + 1) + ')');
                imgs[0].width = imgs[0].getWidth() + 1;
                i = (i + 1) % c;
                tw++;
            }

            // if total width is slightly bigger than
            // actual div width then subtract 1 from each
            // photo width till they match
            i = 0;
            while (tw > rowWidth) {
                var imgs = d_row.select('img:nth-child(' + (i + 1) + ')');
                imgs[0].width = imgs[0].getWidth() - 1;
                i = (i + 1) % c;
                tw--;
            }

            // set row height to actual height + margins
            d_row.height = newht + AnselLayout.border * 2;

            baseLine += c;
        }
    },

    // Handlers
    // Trigger a resize when the screen changes by 10%
    onResize: function()
    {
        var currentWidth = $(AnselLayout.hiddenDiv).getWidth();
       // if (currentWidth * 1.1 < AnselLayout.lastWidth || currentWidth * 0.9 > AnselLayout.lastWidth) {
            AnselLayout.resize();
        //}
    }

}