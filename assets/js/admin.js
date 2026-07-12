/**
 * Script Interaksi Dashboard Admin QR Code Validator.
 *
 * @package QR_Code_Validator
 */

jQuery(document).ready(function($) {
    
    // Fitur Salin Tautan Validasi ke Clipboard
    $('.qrcv-copy-btn').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var link = $btn.data('link');
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(link).then(function() {
                showCopyFeedback($btn);
            }).catch(function(err) {
                fallbackCopyText(link, $btn);
            });
        } else {
            fallbackCopyText(link, $btn);
        }
    });
    
    function showCopyFeedback($btn) {
        var originalText = $btn.text();
        $btn.text('✔️ Tersalin!').addClass('button-disabled').prop('disabled', true);
        
        setTimeout(function() {
            $btn.text(originalText).removeClass('button-disabled').prop('disabled', false);
        }, 1500);
    }
    
    function fallbackCopyText(text, $btn) {
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
        showCopyFeedback($btn);
    }

    // Dynamic Metadata Row Management
    var $container = $('#qrcv-meta-fields-container');
    var $addButton = $('#qrcv-add-meta-btn');

    // Pastikan jika ada default row tapi tidak disembunyikan, hapus class default-row jika ditambah baris baru
    $addButton.on('click', function(e) {
        e.preventDefault();
        
        // Cek jika default-row disembunyikan, kita tampilkan saja dia dulu
        var $hiddenDefault = $container.find('.default-row:hidden');
        if ($hiddenDefault.length > 0) {
            $hiddenDefault.show().removeClass('default-row');
            return;
        }

        // Duplikasi template baris input baru
        var $rowTemplate = $('<div class="qrcv-meta-row">' +
            '<input type="text" name="meta_key[]" placeholder="Label (Contoh: NIK)" class="meta-input">' +
            '<input type="text" name="meta_value[]" placeholder="Nilai (Contoh: 12345678)" class="meta-input">' +
            '<button type="button" class="button qrcv-remove-meta-btn">Hapus</button>' +
        '</div>');

        $container.append($rowTemplate);
    });

    // Delegasi Event Hapus Row
    $container.on('click', '.qrcv-remove-meta-btn', function(e) {
        e.preventDefault();
        var $row = $(this).closest('.qrcv-meta-row');
        
        // Jika hanya tersisa satu baris di container, kosongkan input dan sembunyikan (agar tetap ada baris default tersembunyi)
        if ($container.find('.qrcv-meta-row').length === 1) {
            $row.addClass('default-row').hide().find('input').val('');
        } else {
            $row.remove();
        }
    });

});
