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

- **Key figures:** usable bags in stock, pending requests, deliveries in transit, and donations awaiting screening. Tap a card to jump to the related list.
- **Expiring within 7 days:** units that will expire soon, with how long each has left. They're dispatched first automatically. This panel is a prompt to offer them to hospitals before they go to waste.
- **Stock by blood group:** one tile per group, counting only screened, unexpired units. Tiles marked **Low** are at or below the threshold (5 bags by default), and **Out** means none are left. Tap a tile to see donations of that group.
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
3. Save. Each bag becomes a **unit** with its own code (e.g. `LL000123`) and an expiry date **35 days** after collection. Units are held in **quarantine**: they don't count as stock until they pass lab screening.

**Returning donors** are recognised by first name, last name and phone number. Capital letters, extra spaces and phone punctuation don't matter. The donation is added to their existing record, and their details are refreshed, rather than creating a duplicate person. Two safety checks apply:

- **Donation interval:** a donor must wait **56 days** between donations (set `DONATION_INTERVAL_DAYS` to change it). If they're too early, the app shows the date they can give again.
- **Blood group:** the group must match the one on record. If you think the record is wrong, record it under the group on file and correct it at screening.
- **Deferral:** a donor who failed screening is refused, permanently for HIV or hepatitis, or until their deferral ends for syphilis or malaria.

### Lab screening

**Screening** (in the menu, with a badge showing how many donations are waiting) lists every donation in quarantine, oldest first, with its unit codes and how long they have left.

- **Pass & release:** choose the blood group the lab confirmed (it defaults to the recorded one) and pass. The units become available stock. If you pick a different group, the donation, its units and the donor's record are all re-grouped.
- **Fail:** choose the reason. Every unit is discarded, and the donor is deferred:
  - HIV, hepatitis B or C: **permanently**;
  - syphilis or malaria: **12 months**;
  - a sample problem (clotted, insufficient, haemolysed): not deferred.

A donation can only be screened once. Units that expire while still waiting are marked expired instead of being released.

### Units

**Units** lists every bag individually: its code, group, status, collection and expiry dates, and where it is now (in storage, awaiting the lab, which request and hospital it went to, or why it was discarded).

- Filter by status (*Available*, *Expiring ≤ 7d*, *Awaiting screening*, *Dispatched*, *Expired*, *Discarded*), by blood group, or search a unit code.
- **Discard** takes a unit out of stock for good. You must give a reason (damaged bag, cold-chain breach, haemolysed, clotted, labelling error, other), plus an optional note. Every discard is logged.

### Expiry

- Stock only ever counts unexpired units. A unit stops counting the moment it expires.
- The app checks automatically: expired units are marked as such, and you're notified ("3 units expired: A+ ×2, O- ×1").
- When units come within **7 days** of expiry you get one heads-up notification, and they appear on the dashboard and under **Units → Expiring**.
- **Dispatch always issues the soonest-expiring units first** (FEFO, first-expired-first-out), so the oldest blood is used before it's wasted.

### Editing or removing a donation

On **Donations**, the **Units** column shows whether a donation is *Awaiting screening* or *Screened*.

- **Donor details** (name, contact, address, notes) can always be corrected with **Edit**.
- **Blood type and number of bags** can only change **before screening**, while the units are still in quarantine.
- **Delete** is only offered before screening.

After screening, a unit is part of the stock history, so corrections are explicit: correct the blood group at screening, or **discard** a unit on the Units page with a reason.

### Blood stock page

- **Totals:** units available, units expiring within 7 days, units in quarantine, and units wasted (expired or discarded) in the last 30 days. Each card links to the matching unit list.
- **Per-group table:** available, expiring soon, the next expiry date, units in quarantine, pending demand, and a status (*Healthy*, *Low*, *Out of stock*, or *Short N* when more is pending than available).
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

**Each delivery lists the exact units sent:** open "N units · use by …" on the request card to see every unit code and its use-by date, for your transfusion records.

**Always ask for what the patient needs, even if stock looks low.** If you ask for more than is in stock, a note explains that the blood bank may send a **compatible group** or a **partial amount**. Your request is never refused for stock reasons. When it's dispatched, the notification and the request card show exactly which groups and how many bags were sent.

### Confirming receipt

When the delivery arrives, click **Confirm receipt** on the request. This completes it and lets the blood bank know.

### Cancelling

You can cancel your own request while it is still **Pending**. Once it has been dispatched, contact the blood bank to cancel it.

### Logging a walk-in donor

Use **Log a donor** on your dashboard. The donation is recorded as collected by your hospital and goes into the blood bank's screening queue. It becomes available stock once it passes lab screening.

---

## Notifications

Open **Notifications** in the menu, or tap the bell at the top right on a phone. The red badge shows how many are unread. It updates on its own while the page is open, and a pop-up appears when something new arrives.

| You are | You're notified when |
|---|---|
| **Staff** | A hospital submits a request (emergencies are flagged in red), withdraws a request or confirms receipt; a hospital logs a walk-in donor; a blood group runs low or out; units are about to expire or have expired |
| **Hospital** | Your request is dispatched (including any substitute or partial fill), recorded as received or cancelled, or the blood bank files a request for you |

- Click a notification to mark it read and jump straight to the request it's about.
- Use **Unread** to see only new ones, and **Mark all as read** to clear the badge.

---

## Tips

- Search boxes match any part of a name. On **Requests**, staff can search by doctor or hospital.
- Filter chips (blood type, status) can be combined with search, and each chip shows a count.
- If a form has a problem, it reopens with the faulty fields highlighted and everything you typed still filled in.
- Important actions (approve, cancel, delete) always ask for confirmation first.
