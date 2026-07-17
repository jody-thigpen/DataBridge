<?php

namespace App\Enums;

enum Permission: string
{
    // Platform
    case PlatformOrganizationsManage = 'platform.organizations.manage';
    case PlatformUsersManage = 'platform.users.manage';
    case PlatformSettingsManage = 'platform.settings.manage';
    case PlatformDataSourcesManage = 'platform.data_sources.manage';
    case PlatformCatalogManage = 'platform.catalog.manage';
    case PlatformReportOrdersView = 'platform.report_orders.view';
    case PlatformReportOrdersManage = 'platform.report_orders.manage';
    case PlatformAuditView = 'platform.audit.view';

    // Organization
    case OrgUsersManage = 'org.users.manage';
    case OrgUsersInvite = 'org.users.invite';
    case OrgOrdersCreate = 'org.orders.create';
    case OrgOrdersView = 'org.orders.view';
    case OrgOrdersViewAll = 'org.orders.view_all';
    case OrgReportsView = 'org.reports.view';
    case OrgReportsViewPii = 'org.reports.view_pii';
    case OrgBillingManage = 'org.billing.manage';
    case OrgSettingsManage = 'org.settings.manage';
}
