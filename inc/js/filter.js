function filter() {
	var inputWho, inputWhen, inputComment, filter, table, tr, td, i;
	inputWho = document.getElementById("who");
	inputWhen = document.getElementById("when");
	inputComment = document.getElementById("comment");
	filterWho = inputWho.value.toUpperCase();
	filterWhen = inputWhen.value.toUpperCase();
	filterComment = inputComment.value.toUpperCase();
	table = document.getElementById("list");
	tr = table.getElementsByTagName("tr");

	for (i = 0; i < tr.length; i++) {
		tdWho = tr[i].getElementsByTagName("td")[0];
		tdWhen = tr[i].getElementsByTagName("td")[1];
		tdComment = tr[i].getElementsByTagName("td")[3];

		if (tdWho && tdWhen && tdComment) {
			if ((tdWho.innerHTML.toUpperCase().indexOf(filterWho) > -1) && (tdWhen.innerHTML.toUpperCase().indexOf(filterWhen) > -1) && (tdComment.innerHTML.toUpperCase().indexOf(filterComment) > -1)) {
				tr[i].style.display = "";
			} else {
				tr[i].style.display = "none";
			}
		}
	}
}
