class ProfileView extends InlineCode {


    activate() {
        super.activate();

        let code = _inlineManager.getInlineCode('dungeonroute/table');
        let tableView = code.getTableView();
        tableView.setUser(this.options.user);
    }
}