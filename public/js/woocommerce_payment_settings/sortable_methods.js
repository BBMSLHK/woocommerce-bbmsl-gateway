jQuery(function () {
	jQuery("#sortable_payment_methods tbody").sortable({
		handle: ".handle",
		helper: "clone",
		placeholder: "sortable-placeholder"
	});
});