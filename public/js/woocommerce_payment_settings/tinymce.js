tinymce.init({
	selector: '.tinymce',
	branding: false,
	plugins: 'print preview paste importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons',
	toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist | forecolor backcolor removeformat | charmap emoticons | image media template link anchor | ltr rtl',
	image_advtab: true,
	image_caption: true,
	plugins: 'autoresize',
	min_height: 450,
	width: '100%',
	min_width: '100%',
	max_width: '100%',
	force_hex_style_colors: true,
});