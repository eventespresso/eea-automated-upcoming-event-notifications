<table class="head-wrap" bgcolor="#999999">
    <tbody>
    <tr>
        <td></td>
        <td class="header container">
            <div class="content">
                <table bgcolor="#999999">
                    <tbody>
                    <tr>
                        <td>[CO_LOGO]</td>
                        <td align="right">
                            <h6 class="collapse">[COMPANY]</h6>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </td>
        <td></td>
    </tr>
    </tbody>
</table>

<table class="body-wrap">
    <tbody>
    <tr>
        <td></td>
        <td class="container" bgcolor="#FFFFFF">
            <div class="content">
                <h1><?php esc_html_e('Upcoming Datetime Notification', 'event_espresso'); ?></h1>
                <?php printf(
                    esc_html__(
                        'An upcoming datetime notification has been sent to attendees for following event on %s:',
                        'event_espresso'
                    ),
                    '[SPECIFIC_DATETIME_START] - [SPECIFIC_DATETIME_END]'
                ); ?>
                <table>
                    <tbody>
                    <tr>
                        <td>[EVENT_LIST]</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </td>
        <td></td>
    </tr>
    </tbody>
</table>