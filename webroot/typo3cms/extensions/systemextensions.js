// manually maintained list of system extensions
// mb, 2015-11-22, 2016-01-27

// merge list 'systemExtensionList' into list 'extensionList'

var systemExtensionList = [
	{"key":"core","latest":"latest","versions":["7.6","latest"]},
	{"key":"css_styled_content","latest":"latest","versions":["4.7","6.2","7.6","latest"]},
	{"key":"dbal","latest":"latest","versions":["1.0.0","6.2","7.6","latest"]},
	{"key":"documentation","latest":"latest","versions":["6.2","7.6","latest"]},
	{"key":"felogin","latest":"latest","versions":["4.7","6.2","7.6","latest"]},
	{"key":"fluid_styled_content","latest":"latest","versions":["7.6","latest"]},
	{"key":"form","latest":"latest","versions":["4.7","6.2","7.6","latest"]},
	{"key":"indexed_search","latest":"latest","versions":["6.2","7.6","latest"]},
	{"key":"linkvalidator","latest":"latest","versions":["6.2","7.6","latest"]},
	{"key":"openid","latest":"latest","versions":["4.7","6.2","7.6","latest"]},
	{"key":"recycler","latest":"latest","versions":["4.7","6.2","7.6","latest"]},
	{"key":"rsaauth","latest":"latest","versions":["4.7","6.2","7.6","latest"]},
	{"key":"rtehtmlarea","latest":"latest","versions":["4.7","6.2","7.6","latest"]},
	{"key":"saltedpasswords","latest":"latest","versions":["4.7","6.2","7.6","latest"]},
	{"key":"scheduler","latest":"latest","versions":["4.7","6.2","7.6","latest"]},
	{"key":"sys_action","latest":"latest","versions":["4.7","6.2","7.6","latest"]},
	{"key":"taskcenter","latest":"latest","versions":["4.7","6.2","7.6","latest"]},
	{"key":"workspaces","latest":"latest","versions":["6.2","7.6","latest"]}
];

// var extensionList needs to exist at this point
// merge var systemExtensionList into var extensionList
// we expect the lists to be sorted

var t3extkey;
var j = 0;

// Loop on all extension to find an exact match first
for (var i = 0; i < systemExtensionList.length; i++) {
	t3extkey = systemExtensionList[i].key;
	while (j < extensionList.length) {
		if (t3extkey < extensionList[j].key) {
			extensionList.splice(j, 0, systemExtensionList[i]);
			t3extkey = '';
			j++;
			break;
		} else if (extensionList[j].key == t3extkey) {
			extensionList.splice(j, 1, systemExtensionList[i]);
			t3extkey = '';
			j++;
			break;
		} else {
			j++;
		}
	}
	if (t3extkey.length) {
		extensionList.push(systemExtensionList[i]);
		j = extensionList.length;
	}
}
