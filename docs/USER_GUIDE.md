# User guide

LifeLine has two portals. Switch between them with the tabs at the top of the sign-in page.

| Who | Sign in at | Can do |
|---|---|---|
| **Blood bank staff** | `/admin/login` | Everything: inventory, donations, approving and tracking all requests |
| **Partner hospitals** | `/login` | Request blood, follow their own deliveries, log walk-in donors |

On phones, tap **☰** at the top left to open the menu.

---

## For blood bank staff

### Dashboard

- **Key figures:** bags in stock, pending requests, deliveries in transit, and bags donated this month. Tap a card to jump to the related list.
- **Stock by blood group:** one tile per group. Tiles marked **Low** are at or below the threshold (5 bags by default), and **Out** means none are left. Tap a tile to see donations of that group.
- **Requests & deliveries:** the triage queue, in the same order as the Requests page.
- **Recent donations:** the latest people who gave blood.

### The request queue

Pending requests are sorted the way you should work through them:

1. **Emergency** requests first (also flagged with a red edge), then **Urgent**, then **Routine**.
2. Within the same priority, the earliest **Needed by** time comes first. A request past its needed-by time shows *overdue* in red.
3. Then oldest first.

In-transit requests come next, and finished ones last.

### Allocating and dispatching

Hospitals can ask for more than you have in stock. You decide what to send.

1. Click **Allocate & dispatch** on a pending request.
2. The dialog lists every group that is safe for this patient, with current stock and a suggested split:
   - the patient's own group first;
   - then other compatible groups, the best-stocked first;
   - **O−** (the universal donor) last, so it's kept for when nothing else will do.
3. Adjust the numbers if you need to. The total can't exceed what was requested or what's in stock.
   - Sending fewer bags than requested is a **partial fill**. The hospital is told how many are coming.
   - Sending another group is a **compatible substitute**. The hospital is told exactly what was sent.
4. Click **Dispatch**. Stock is deducted from each group you chose and the request moves to **In transit**.

### Receipt and cancellation

- The **hospital confirms receipt** when the blood arrives, and you get a notification.
- If a hospital confirms by phone instead, use **Record receipt**. The activity log notes that staff recorded it on the hospital's behalf.
- **Cancel** works on pending or in-transit requests. Cancelling an in-transit request returns every bag to the group it came from.

To file a request for a hospital (for example, one phoned in), use **New request** and pick the hospital.

### Activity log

**Activity** lists every action on requests and donations: who did it, when, and the details (such as "Sent 2 AB−, 1 O−" or "bags 1 → 2"). Filter it by type or by a person or hospital name. Each request card also shows who did each step under its tracker.

### Recording a donation

1. Click **Record donation**.
2. Fill in the donor's details (age must be 16–65), choose the blood type, and enter the number of bags (1–2).
3. Save. The bags are added to stock straight away.

**Returning donors** are recognised by first name, last name and phone number. Capital letters, extra spaces and phone punctuation don't matter. The donation is added to their existing record, and their details are refreshed, rather than creating a duplicate person. Two safety checks apply:

- **Donation interval:** a donor must wait **56 days** between donations (set `DONATION_INTERVAL_DAYS` to change it). If they're too early, the app shows the date they can give again.
- **Blood group:** the group must match the one on record. If the lab has re-grouped the donor, correct their existing donation first.

### Editing or removing a donation

On **Donations**, use **Edit** to correct details. If you change the number of bags or the blood type, stock is adjusted for you.

You can't reduce, move or delete a donation whose bags have already been sent to hospitals. The app explains why instead of letting stock go wrong.

### Blood stock page

- **Totals:** bags available, collected all-time, dispatched to hospitals, and pending demand.
- **Per-group table** with a status for each group: *Healthy*, *Low*, *Out of stock*, or *Short N* (more is pending than is available).
- **Compatibility chart:** which donor groups each patient group can safely receive.

---

## For hospitals

### Dashboard

- How many of your requests are pending, on the way, received or cancelled.
- **Your deliveries:** a tracker for each request (Requested → Dispatched → Received) with times and who did each step.
- **Available now:** current stock for every blood group, so you know what to expect.

### Requesting blood

1. Click **Request blood**.
2. Enter the requesting doctor's name ("Dr." is added for you).
3. Pick the patient's blood type. Each button shows how many bags are in stock.
4. Enter the number of bags (1–20).
5. Optionally set **Needed by**, the time the blood must arrive.
6. Choose a **priority**:
   - **Routine:** a planned procedure or restock.
   - **Urgent:** needed within hours.
   - **Emergency:** life-threatening. Blood bank staff get an emergency alert and the request goes to the top of their queue.
7. Submit. The request shows as **Pending** until the blood bank dispatches it.

**Always ask for what the patient needs, even if stock looks low.** If you ask for more than is in stock, a note explains that the blood bank may send a **compatible group** or a **partial amount**. Your request is never refused for stock reasons. When it's dispatched, the notification and the request card show exactly which groups and how many bags were sent.

### Confirming receipt

When the delivery arrives, click **Confirm receipt** on the request. This completes it and lets the blood bank know.

### Cancelling

You can cancel your own request while it is still **Pending**. Once it has been dispatched, contact the blood bank to cancel it.

### Logging a walk-in donor

Use **Log a donor** on your dashboard. The donation is added to the central stock and recorded as collected by your hospital.

---

## Notifications

Open **Notifications** in the menu, or tap the bell at the top right on a phone. The red badge shows how many are unread. It updates on its own while the page is open, and a pop-up appears when something new arrives.

| You are | You're notified when |
|---|---|
| **Staff** | A hospital submits a request (emergencies are flagged in red), withdraws a request or confirms receipt; a hospital logs a walk-in donor; or a blood group runs low or out |
| **Hospital** | Your request is dispatched (including any substitute or partial fill), recorded as received or cancelled, or the blood bank files a request for you |

- Click a notification to mark it read and jump straight to the request it's about.
- Use **Unread** to see only new ones, and **Mark all as read** to clear the badge.

---

## Tips

- Search boxes match any part of a name. On **Requests**, staff can search by doctor or hospital.
- Filter chips (blood type, status) can be combined with search, and each chip shows a count.
- If a form has a problem, it reopens with the faulty fields highlighted and everything you typed still filled in.
- Important actions (approve, cancel, delete) always ask for confirmation first.
