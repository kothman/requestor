# Equipment Request System
This application offers a convenient way to streamline company employees checking out work equipment.

### Authentication
- [ ] Require employees to login using their existing Microsoft email address and password

### User roles
- [ ] Requester
  - The standard role, allowing access to view available equipment and make / view requests
- [ ] Approver
  - Has the same privileges as a requester, but can also manage requests made by requesters
- [ ] Admin
  - Has the same privileges as a requester, but can also manage and modify all user accounts

### After Authentication
- [ ] Automatically sync available equipment from Snipe-IT (or some other API)
- [ ] Display the Dashboard, with
  - [ ] A navigation menu bar (App name / logo, "Dashboard", "New Request", "Account")
  - [ ] Pending / past requests
  - [ ] Equipment available for authenticated users to request

### Request Form
Allow authenticated users to make equipment reservation requests based on current availability
- [ ] Prefilled with First and Last Name, Email (not editable)
- [ ] Required date (and optional time) range
- [ ] Required phone input
- [ ] Notes textarea
- [ ] Allow searching for equipment by name, category, etc


### After a request is submitted
- [ ] An approver can view, approve, deny, and requests, or set it as pending more information
- [ ] Reminder / Notification emails
  - [ ] Approver & Requester (separate email tempates)
    - [ ] Reminder sent out XX days before the equipment is needed, with follow-up day-of
	- [ ] Notification sent on submission
	- [ ] Calendar invite in notification email
	- [ ] Reminder to return equipment, with follow up if not checked back in
	  - [ ] Approver can check equipment in
- [ ] Equipment that has been requested is unavailable for reservation during the allocated request time
