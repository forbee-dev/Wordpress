<?php
$pros_header = get_field('pros_table_header');
$cons_header = get_field('cons_table_header');
$pros = get_field('pros');
$cons = get_field('cons');
?>
<div class="pros-cons-block">
    <table>
        <thead>
            <tr>
                <th><?php echo esc_html($pros_header); ?></th>
                <th><?php echo esc_html($cons_header); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <ul>
                        <?php foreach ($pros as $pro): ?>
                            <li><?php echo esc_html($pro['pro']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </td>
                <td>
                    <ul>
                        <?php foreach ($cons as $con): ?>
                            <li><?php echo esc_html($con['con']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </td>
            </tr>
        </tbody>
    </table>
</div>
