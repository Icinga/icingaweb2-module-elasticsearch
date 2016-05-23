# Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+

%define revision    1
%define pre_release_tag a
%define basedir     %{_datadir}/icingaweb2/modules/elasticsearch

Name:       icingaweb2-module-elasticsearch
Version:    0.2
Summary:    Elasticsearch Module
Group:      Applications/System
License:    GPLv2+
URL:        https://dev.icinga.org/projects/icingaweb2-module-elasticsearch
Source0:    https://github.com/Icinga/icingaweb2-module-elasticsearch/archive/v%{version}%{?pre_release_tag}.tar.gz
Vendor:     Icinga Team <info@icinga.org>
Packager:   Icinga Team <info@icinga.org>

%if %{?pre_release_tag}
Release:    0.%{revision}.%{pre_release_tag}%{?dist}
%else
Release:    %{revision}%{?dist}
%endif

BuildArch:      noarch
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}

Requires:   icingaweb2 >= 2.3.0

%description

%files
%defattr(-,root,root)
%doc AUTHORS COPYING
%{basedir}/application
%{basedir}/configuration.php
%{basedir}/library
%{basedir}/module.info


%prep
%setup -q -c

%build

%install
mkdir -p %{buildroot}/%{basedir}
cp -prv application %{buildroot}/%{basedir}
cp -prv library %{buildroot}/%{basedir}
cp -pv configuration.php %{buildroot}/%{basedir}
cp -pv module.info %{buildroot}/%{basedir}

%clean
rm -rf %{buildroot}


%pre

%post


%preun

%postun

