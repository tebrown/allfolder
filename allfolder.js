function rcmail_all()
{
    if (rcmail.env.uid || rcmail.message_list && rcmail.message_list.get_selection().length)
    {
        var a =
            rcmail.env.uid ? rcmail.env.uid : rcmail.message_list.
        get_selection().join(","),
            b = rcmail.set_busy(!0, "loading");
        rcmail.http_post("plugin.allmail",
            "_uid=" + a + "&_mbox=" +
            urlencode(rcmail.env.mailbox), b)
    }
}

function rcmail_all_contextmenu(a)
{
    (rcmail.env.uid || rcmail.message_list && rcmail.message_list.get_selection().length) && 0 < rcmail.message_list.get_selection().length && rcmail_all(a)
}


$(document).ready(function ()
{
    window.rcmail && ("larry" != rcmail.env.skin && $(".allfolder").text(""),
        rcmail.addEventListener("init", function ()
        {
            rcmail.env.all_folder && rcmail.add_onload("rcmail_all_init()");
            rcmail.register_command("plugin.allmail", rcmail_all, rcmail.env.uid && rcmail.env.mailbox != rcmail.env.all_folder);
            rcmail.message_list && rcmail.message_list.addEventListener("select", function (a)
            {
                rcmail.enable_command("plugin.allmail", 0 < a.get_selection().length && rcmail.env.mailbox != rcmail.env.all_folder)
            });
            rcmail_all_icon()
        }))
});

function rcmail_all_icon()
{
    var a;
    if (rcmail.env.all_folder && rcmail.env.all_folder_icon && (a = rcmail.get_folder_li(rcmail.env.all_folder, "", !0)))
        "larry" != rcmail.env.skin ? $(a).css("background-image",
            "url(" +
            rcmail.env.all_folder_icon +
            ")") : $(a).addClass("all"),
    $(a).insertAfter("#mailboxlist .inbox"), a =
        $("._all"), $(a.get(0)).insertBefore("#rcmContextMenu .drafts")
}

function rcmail_all_init()
{
    window.rcm_contextmenu_register_command && rcm_contextmenu_register_command("all",
        "rcmail_all_contextmenu",
        rcmail.gettext("allfolder.buttontitle"),
        "delete", null, !0)
};
