# Working in the repository

## Git

Branch formats are as follows:
- `<issue number>-<slug-description-of>`
- `1234-create-the-feature`
- `2345-fix-the-issue`

## Github

You can use `gh issue view <issue number> --repo RaiderIO/keystone.guru --json number,title,body,labels,comments`
to request info from Github. Any call to `gh issue view` MUST be accompanied by `--json` to prevent deprecation warnings
and the command failing.

# Project-specific conventions
