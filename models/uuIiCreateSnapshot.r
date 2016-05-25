# \brief 	Get the path name for the snapshot in the vault, which will take the form
#				<vaultRoot>/<name_of_owner_of_dataset>/<name_of_dataset>/<unix_timestamp>/
#
# \param[in]	datasetPath 	Full path to the dataset, including zone and name of dataset
# \param[in]	vaultRoot 		Full path to the root of the vault of the study, including zone
# \param[in]	datasetName 	Name of the dataset directory that resides in the intake root
# \param[out]	fullPath 		The full path to the collection the snapshot should be written to
uuIiGetSnapshotPath(*datasetPath, *vaultRoot, *datasetName, *fullPath) {
	foreach(*row in SELECT COLL_OWNER_NAME, COLL_PARENT_NAME WHERE COLL_NAME = *datasetPath) {
		*ownername = *row.COLL_OWNER_NAME;
	}
	msiGetIcatTime(*time, "unix");
	*fullPath = "*vaultRoot/*ownername/*datasetName/*time"
}

# \brief 	Create a snapshot of a single dataset, by copying
# 			the dataset to the vault
#
# \param[in]	intakeRoot 		The root of the intake study
#								This is the complete collection
#								name, including the zone, and
#								probably ending with grp-intake-<something>
# \param[in]	vaultRoot		The root of the studies vault area, so probably
#								the same as the intakeRoot, except ending with
#								grp-vault-<something>
# \param[in]	datasetName		The name of a directory inside intakeRoot
# \param[in]	status			0 if succesful, statuscode otherwise
uuIiCreateSnapshot(*intakeRoot, *vaultRoot, *datasetName, *status) {
	*datasetPath = "*intakeRoot/*datasetName";
	uuIiGetSnapshotPath(*datasetPath, *vaultRoot, *datasetName, *fullPath);
	*status = *fullPath;
}