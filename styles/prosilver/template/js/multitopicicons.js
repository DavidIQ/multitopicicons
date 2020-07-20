$(function () {
    const $topic_icons = $("input[name='topic_icons[]']");
    $topic_icons.change(disable_toggle);
    disable_toggle();

    function disable_toggle() {
        const $checked_topic_icons = $topic_icons.filter(':checked');
        const $unchecked_topic_icons = $topic_icons.filter(':not(:checked)');
        if ($checked_topic_icons.length >= max_topic_icons) {
            $unchecked_topic_icons.attr('disabled', 'disabled');
        } else {
            $unchecked_topic_icons.removeAttr('disabled');
        }
    }
});
