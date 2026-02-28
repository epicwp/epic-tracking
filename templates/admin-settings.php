<div class="wrap">
    <h1>Epic Tracking Settings</h1>

    <form method="post" action="options.php">
        <?php settings_fields('ept_settings_group'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">Exclude Roles from Tracking</th>
                <td>
                    <fieldset>
                        <?php foreach ($allRoles as $slug => $name) : ?>
                            <label>
                                <input type="checkbox"
                                       name="ept_settings[excluded_roles][]"
                                       value="<?php echo esc_attr($slug); ?>"
                                       <?php checked(in_array($slug, $settings['excluded_roles'])); ?>>
                                <?php echo esc_html($name); ?>
                            </label><br>
                        <?php endforeach; ?>
                        <p class="description">Visits and events from these roles will not be tracked.</p>
                    </fieldset>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
