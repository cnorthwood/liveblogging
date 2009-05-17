var liveblog_last_status;

var COMMENTS_ODD  = 0;
var COMMENTS_EVEN = 1;

var comments_last_parity = jQuery('.commentlist li').size() % 2;

// Build HTML for an entry
function liveblog_build_entry(entry)
{
    return "<p><strong>" + entry.dt + "</strong></p>" + entry.entry +
           '<div style="width:620px; height:1px; background-color:#6f6f6f; margin-bottom:3px;"></div>';
}

// Only call me once per entry build! I use a state variable to prevent
// needlessly counting the length of comments for each entry.
function comments_build_entry (entry)
{
  var classes  = "comment depth-1 " +
    ((comments_last_parity === COMMENTS_ODD) ?
      "even thread-even" :
      "odd alt thread-odd thread-alt");

  comments_last_parity = (comments_last_parity === COMMENTS_ODD) ?
    COMMENTS_EVEN :
    COMMENTS_ODD;

  return '<li style="display: none;" ' +
              'id="comment-' + entry.newid + '" class="' + classes + '">' +
      '<div id="div-comment-' + entry.newid + '">' +
        '<div class="comment-author vcard">' +
          '<cite class="fn">' + entry.author + '</cite> ' +
          '<span class="says">says:</span>' +
        '</div>' +
        '<div class="comment-meta commentmetadata">' +
          '<a href="' + entry.url + '">' +
            entry.date +
          '</a>' +
        '</div>' +
        entry.text +
        '<div class="reply"></div>' +
      '</div>' +
    '</li>';
}

// Handle incoming events
function liveblog_handle_event(data)
{
    eval("var entry = "+data+";"); // data is JSON string
    if (entry.type == "liveblog")
    {
        var entrid = 'liveblog-entry-' + entry.entryid;
        var elem = document.getElementById(entrid);
        if (elem === null)
        {
            // Entry doesn't already exist
            jQuery('#liveblog').prepend('<div id="' + entrid + '" style="display: none; position: absolute;">' +
                                        liveblog_build_entry(entry) + '</div>');
    
            var newun = jQuery('#' + entrid)
            newun.fadeTo(0, 0,
              function () {
                  // now the element is in DOM we can ask how big it is with height()
                  newun.css('margin-bottom', ((-1 * newun.height()) + 3) + 'px');
                  // now we have a good margin we can add the thing in properly.
                  newun.css('display', 'block');
                  newun.css('position', 'relative');
    
                  newun.animate({marginBottom: 0}, 1000, 'swing',
                                function () { newun.fadeTo('slowly', 1); });
              });
        }
        else
        {
            // Entry exists, get it updated
            elem.innerHTML = liveblog_build_entry(entry);
        }
    }
    else if (entry.type == "comment")
    {
        var elem = document.getElementById('comment-' + entry.newid);
        if (elem === null)
        {
            var el = jQuery(comments_build_entry(entry));
            el.fadeOut(0,
              function () {
                  jQuery('.commentlist').append(el);
                  el.fadeIn('slow');
              });
        }
    }
}

// Update status as appropriate
function liveblog_handle_statechange(newstate)
{
    if (newstate === liveblog_last_status) {
        return;
    }
    var msg = null;
    // Set new status message appropriate.
    if (newstate == 5)
    {
        msg = 'Updates to the live blog will automatically appear, there is no need to refresh.';
    }
    else if (newstate == 6)
    {
        msg = 'Disconnected from the live blog. Updates will no longer automatically appear.';
    }
    if (msg)
    {
        var statuselem = jQuery("#liveblog-status");
        liveblog_last_status = newstate;
        statuselem.fadeOut("fast",
          function () {
              statuselem.html(msg);
              statuselem.fadeIn("fast");
          });
    }
}
