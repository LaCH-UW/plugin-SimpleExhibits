<div class="table-responsive">
    <table class="full">
        <thead>
            <tr>
                <?php echo browse_sort_links(array(
                    __('Title') => 'title',
                    __('Slug') => 'slug',
                    __('Last Modified') => 'updated'), array('link_tag' => 'th scope="col"', 'list_tag' => ''));
                ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach (loop('simple_exhibits_pages') as $simplePage): ?>
            <tr>
                <td>
                    <span class="title">
                        <a href="<?php echo html_escape(record_url('simple_exhibits_page')); ?>">
                            <?php echo metadata('simple_exhibits_page', 'title'); ?>
                        </a>
                        <?php if(!metadata('simple_exhibits_page', 'is_published')): ?>
                            (<?php echo __('Private'); ?>)
                        <?php endif; ?>
                    </span>
                    <ul class="action-links group">
                        <li><a class="edit" href="<?php echo html_escape(record_url('simple_exhibits_page', 'edit')); ?>">
                            <?php echo __('Edit'); ?>
                        </a></li>
                        <li><a class="delete-confirm" href="<?php echo html_escape(record_url('simple_exhibits_page', 'delete-confirm')); ?>">
                            <?php echo __('Delete'); ?>
                        </a></li>
                    </ul>
                </td>
                <td><?php echo metadata('simple_exhibits_page', 'slug'); ?></td>
                <td><?php echo __('<strong>%1$s</strong> on %2$s',
                    metadata('simple_exhibits_page', 'modified_username'),
                    html_escape(format_date(metadata('simple_exhibits_page', 'updated'), Zend_Date::DATETIME_SHORT))); ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>